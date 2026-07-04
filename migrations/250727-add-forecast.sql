ALTER TABLE `ny_results_log` ADD COLUMN `forecast` DOUBLE AFTER `value`;
ALTER TABLE `ld_results_log` ADD COLUMN `forecast` DOUBLE AFTER `value`;
ALTER TABLE `ny_results_log_test` ADD COLUMN `forecast` DOUBLE AFTER `value`;
