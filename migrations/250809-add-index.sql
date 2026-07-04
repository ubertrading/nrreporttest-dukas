CREATE INDEX idx_newsId_eventTime_group
ON ny_results_log (`news_id`, `event_time`, `timestamp`);

CREATE INDEX idx_newsId_eventTime_group
ON ld_results_log (`news_id`, `event_time`, `timestamp`);

CREATE INDEX idx_newsId_eventTime_group
ON ny_results_log_test (`news_id`, `event_time`, `timestamp`);

