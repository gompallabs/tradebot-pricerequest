Feature:
  I may want to import tick data from csv file or make simple api call
  In order to know how to exploit this data, I need to poke on formats
  I would like to aggregate the tickData to OHLC timeseries.
  I have simple guidelines/ideas:
   - Aggregation is called after import of tickData and before push as a 1 second data to the store
   - Aggregation could in the future also be called to transform the 1 second data to any unit of time
   - Why 1 second ? just a tradeoff between keeping information persistent and having enough hardware to compute

  What parameters do I suppose are a good start, given previous tests ?
  -> Current RedisTimeSeries is a monocolumn structure and will reach significant length
  -> Maybe we can leverage labels and store each datapoint (column) as a ts

  Scenario:
    Given I download the "1" "last" files on "https://public.bybit.com" at the slug "/trading/BTCUSDT" in the "var/data/download/" directory
    And I parse the files
    Then I aggregate the tick data with a "1" second step and push it to datastore under the key "btc-usdt"

  Scenario:
    Given I download the "1" "last" files on "https://public.bybit.com" at the slug "/trading/BTCUSDT" in the "var/data/download/" directory
    And I parse the files
    Then I aggregate the tick data with a "1" second step and push it to datastore under the key "btc-usdt"
    Then I fetch the timeseries "btc-usdt" I use redis aggregation to build a "10 seconds" time series