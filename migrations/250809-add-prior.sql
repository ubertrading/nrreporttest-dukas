ALTER TABLE `ny_results_log` ADD COLUMN `prior` DOUBLE AFTER `forecast_avg`;
ALTER TABLE `ld_results_log` ADD COLUMN `prior` DOUBLE AFTER `forecast_avg`;
ALTER TABLE `ny_results_log_test` ADD COLUMN `prior` DOUBLE AFTER `forecast_avg`;
