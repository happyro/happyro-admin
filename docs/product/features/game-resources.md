# 游戏资源

## 状态

开发中

## 第一版

- `物品图鉴`：从本地 Renewal 物品快照查询物品 ID、名称、AegisName、类型、价格和服务器脚本。
- `发放物品`：选择角色和物品，通过游戏邮件发送物品附件。
- 物品名称和界面文案使用国际化资源；物品快照来源记录在资源文件中。

## 数据来源

- 服务器属性来自 HappyRO Server `db/re/item_db_*.yml`。
- 客户端 `itemInfo_true.lub` 是 Lua 5.1 字节码，已冗余保存在本地资源目录；说明和资源名通过离线快照写入 `descriptions.json`、`icon-map.json`。
- 原始 kRO 资源来自 `inputs/runtime/kro-20211105/client/data.grf`，全量解压结果位于被 Git 忽略的 `work/grf-extract/kro-20211105/data/`。
- 解压清单 `manifest.json` 保存原始路径、规范化路径、文件大小和 SHA-256；提取工具位于 `repos/happyro-gateway/tools/extract-grf.mjs`。
- 管理后台通过可配置的 `GAME_RESOURCE_ROOT` 读取本地资源，默认指向上述解压目录，不把大体积 GRF 或全量解压文件提交到 Git。

## 资源加载

- 列表图标使用 `texture/유저인터페이스/item/<资源名>.bmp`，详情插图使用 `texture/유저인터페이스/collection/<资源名>.bmp`。
- Laravel `ItemCatalog` 根据物品 ID、官方资源名映射和 manifest 解析本地文件，并通过受 `auth:sanctum` 与 `players.view` 保护的接口返回图片。
- `/api/resources/items/{id}/icon` 返回 24×24 游戏图标；`/api/resources/items/{id}/illustration` 返回通常为 75×100 的详情插图。
- 图片响应使用 `Cache-Control: public, max-age=86400`；前端列表固定 48×64 容器，详情固定 75×100，缺失插图时回退到图标或本地占位图。
- 客户端 BMP 说明中的颜色控制码和空白占位行在 Laravel 服务层清理后再返回，避免把 `^777777` 等内部标记显示给管理员。

## 验收记录

- GRF 全量提取 `148,805/148,805` 成功，解压后约 `11.4 GiB`。
- 物品 ID `1101` 的说明可正常返回并清理客户端格式码。
- 物品 ID `1107` 的详情插图可解析为 `75×100` BMP。
- 物品 ID `1100` 等没有客户端说明的物品明确显示缺省文案，不使用服务器脚本伪造说明。

## 安全边界

- 发放权限使用 `players.edit`，查询使用 `players.view`。
- 第一版只支持邮件发放，不直接写入 `inventory`，避免在线角色内存状态和物品唯一 ID 冲突。
- 每次发放写入审计事件，后续增加发放记录页面。

## 待办

- 补充其他语言的官方物品说明快照，并按后台当前语言选择说明语言。
- 增加按账号选择角色和物品选择器。
- 增加发放记录查询和重复提交保护。
- 评估通过游戏服务接口支持直接发放背包，而不是直接操作背包表。
