/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

create table kload_settings
(
    name  varchar(50) not null,
    value text        not null,
    constraint name unique (name)
) collate = utf8mb4_unicode_ci;

INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('backgrounds', '{"enable":1,"random":1,"duration":5000,"fade":500}');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('community_name', 'K-Load');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('description', '');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('logo', '');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('messages', '{"random":1,"duration":5000,"fade":250,"list":[]}');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('music', '{"enable":1,"random":1,"volume":15,"source":"files","order":[]}');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('rules', '{"duration":2000,"list":[]}');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('staff', '{"duration":400,"list":[]}');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('version', '2.6.0');
INSERT INTO `k-load`.kload_settings (name, value)
VALUES ('youtube', '{"enable":0,"random":0,"volume":0,"list":[]}');