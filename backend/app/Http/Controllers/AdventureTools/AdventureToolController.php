<?php

namespace App\Http\Controllers\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\Auth\GameSessionPrincipal;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdventureTools\ApplyGameRulesRequest;
use App\Http\Requests\AdventureTools\RunCharacterMaintenanceRequest;
use App\Services\GameServer\ApplyGameServerSettingsService;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\GameServerSettingRegistry;
use App\Services\GameServer\SubmitGameServerCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdventureToolController extends Controller
{
    public function __construct(
        private GameServerGateway $gateway,
        private GameServerSettingRegistry $registry,
        private SubmitGameServerCommandService $submit,
        private ExecuteGameServerCommandService $execute,
        private ApplyGameServerSettingsService $applySettings,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $values = $this->gateway->battleConfig();
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }

        return response()->json(['data' => [
            'administrator' => $principal->administrator,
            'characterMaintenanceAllowed' => $this->policyAllows($values['game_tools_character_maintenance_policy'] ?? 2, $principal),
            'gameSettingsAllowed' => $this->policyAllows($values['game_tools_game_settings_policy'] ?? 2, $principal),
        ]]);
    }

    public function character(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->authorizePolicy('game_tools_character_maintenance_policy', $principal);

            return response()->json(['data' => $this->gateway->characterSnapshot($principal->characterId)]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function maintain(RunCharacterMaintenanceRequest $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->authorizePolicy('game_tools_character_maintenance_policy', $principal);
            $data = $request->validated();
            $submission = $this->submit->submitForGameAccount(new GameServerCommandRequest(
                $data['idempotency_key'],
                GameServerCommandType::from($data['type']),
                'character',
                (string) $principal->characterId,
                $data['payload'] ?? [],
            ), $principal->accountId);
            if ($submission->created) {
                $this->execute->execute($submission->command->id);
            }

            return response()->json(['data' => $this->gateway->characterSnapshot($principal->characterId)]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function gameRules(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $values = $this->gateway->battleConfig();
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
        abort_unless($this->policyAllows($values['game_tools_game_settings_policy'] ?? 2, $principal), 403);

        $definitions = [];
        foreach ($this->registry->adventureToolDefinitions() as $definition) {
            $definitions[$definition->key] = [
                'key' => $definition->key,
                'minimum' => $definition->minimum,
                'maximum' => $definition->maximum,
                'unit' => $definition->unit,
            ];
        }

        return response()->json(['data' => [
            'values' => array_intersect_key($values, $definitions),
            'definitions' => $definitions,
        ]]);
    }

    public function applyGameRules(ApplyGameRulesRequest $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->authorizePolicy('game_tools_game_settings_policy', $principal);
            $data = $request->validated();
            $this->applySettings->applyForGameAccount($data['changes'], $data['reason'], $principal->accountId);

            return response()->json(['data' => ['values' => $this->gateway->battleConfig()]]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    private function principal(Request $request): GameSessionPrincipal
    {
        return $request->attributes->get('game_session');
    }

    private function authorizePolicy(string $key, GameSessionPrincipal $principal): void
    {
        $values = $this->gateway->battleConfig();
        abort_unless($this->policyAllows($values[$key] ?? 2, $principal), 403);
    }

    private function policyAllows(int $policy, GameSessionPrincipal $principal): bool
    {
        return $policy === 2 || ($policy === 1 && $principal->administrator);
    }

    private function gatewayError(GameServerGatewayException $exception): JsonResponse
    {
        $status = match (true) {
            in_array($exception->errorCode, ['character_offline', 'reset_failed'], true) => 409,
            in_array($exception->errorCode, ['unavailable', 'map_server_unavailable'], true) => 503,
            default => 502,
        };

        return response()->json(['error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()]], $status);
    }
}
