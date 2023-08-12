Feature:
  Transform current output in timeseries: getting from a monocolumn structure to split series
  Split as we resample and push to datastore

  Scenario: I push the series
    Given I download the "1" "first" files on "https://public.bybit.com" at the slug "/trading/BTCUSDT" in the "var/data/download/" directory
    And I parse the files
    And I aggregate the tick data with a "1" second step and I split the aggregate into series with labels
    And i push the series with labels to redis under keys "BTCUSDT" with appended label
    Then the series should exist under the key "BTCUSDT"

  Scenario: I pull the MRANGE data
    Given the series should exist under the key "BTCUSDT"
    And I request the OHLCV data between "2020-01-01" and now

#   TS.MRANGE
#   Query a range across multiple time series by filters in forward direction
#     TS.MRANGE fromTimestamp toTimestamp                                                     Required
#     [LATEST]
#     [FILTER_BY_TS ts...]                                                                    Required
#     [FILTER_BY_VALUE min max]
#     [WITHLABELS | SELECTED_LABELS label...]
#     [COUNT count]
#     [[ALIGN align] AGGREGATION aggregator bucketDuration [BUCKETTIMESTAMP bt] [EMPTY]]
#     FILTER filterExpr...
#     [GROUPBY label REDUCE reducer]

#  FILTER
#     filters time series based on their labels and label values. Each filter expression has one of the following syntaxes:
#     label=value, where label equals value
#     label!=value, where label does not equal value
#     label=, where key does not have label label
#     label!=, where key has label label
#     label=(value1,value2,...), where key with label label equals one of the values in the list
#     label!=(value1,value2,...), where key with label label does not equal any of the values in the list
