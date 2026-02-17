CREATE TABLE IF NOT EXISTS `{$pfx}order_flag_item` (
  `id_order_flag_item` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `icon` varchar(64) NOT NULL,
  `color` char(7) NOT NULL,
  `date_add` datetime NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  PRIMARY KEY (`id_order_flag_item`)
) ENGINE={$engine};