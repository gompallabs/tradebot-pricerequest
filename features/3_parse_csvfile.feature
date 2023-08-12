Feature: Download files and uncompress them. Parse to RedisTs
  Limitation: RedisTs stores with uniq identifier the timestamp in millisecond
  In the same millisecond there can be several trades with different prices and volumes of course
  So we should either store as OHLCV with buy/sell volumes or as a 1 second aggregate which may add more slippage
  To keep precision / maintainability favourable to an achievable task we prefer a 1 sec aggregate
  Keeping a full tracability involves storing uuids and have much longer series. But we're not an exchange.
  The 1 second aggregation gives some more work on data processing

  Scenario:
    Given I browse the url "https://public.bybit.com" at the slug "/trading/BTCUSDT" with the dom crawler
    Then I can list the files
    And I download the first file of the list in the "var/data/download/" directory
    Then I decompress the file
    And I should parse the csv file
    And I aggregate the tick data by one second step
    And I store the data into Redis under the key "test-sample"
