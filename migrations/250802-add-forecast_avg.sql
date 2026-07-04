ALTER TABLE `ny_results_log` ADD COLUMN `forecast_avg` DOUBLE AFTER `forecast`;
ALTER TABLE `ld_results_log` ADD COLUMN `forecast_avg` DOUBLE AFTER `forecast`;
ALTER TABLE `ny_results_log_test` ADD COLUMN `forecast_avg` DOUBLE AFTER `forecast`;
