/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

create table kload_users
(
    id         int unsigned auto_increment primary key,
    name       varchar(32) null comment 'steam name',
    steamid    bigint                                not null comment 'steamid, e.g. 76561198...',
    steamid2   varchar(20)                           not null comment 'steam2 id, e.g. STEAM_...',
    steamid3   varchar(20)                           not null comment 'steam3 id, e.g. [U:1:...',
    admin      tinyint(1) default 0 null comment 'is the user an admin?',
    perms      text null comment 'list of perms, inactive when admin = 0',
    settings   text null comment 'user settings in JSON',
    registered timestamp default current_timestamp() not null comment 'date when joined',
    constraint kload_users_index unique (steamid, steamid2, steamid3)
) collate = utf8mb4_unicode_ci;