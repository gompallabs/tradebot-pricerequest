Feature:

  Background:
    Given I browse the url "https://public.bybit.com" at the slug "/trading/BTCUSDT" with the dom crawler
    Then I can list the files
    And I download the first file of the list in the "var/data/download/" directory
    Then I decompress the file
    And I should parse the csv file
    And I want to parse the file to a simple array with time as key and price as value
    And I store the data into Redis under the key "BTCUSDT-json"

    Scenario: I create an empty Ts
      Given I remove the ts "BTCUSDT"
      Given I have a test-sample with key "BTCUSDT-json"
      And the ts named "BTCUSDT" exists and has no data
      Then I create a TimeSeries in redis with key "BTCUSDT" and expiration "100000"

    Scenario: I add the first datapoint to the test sample
      Given I remove the ts "BTCUSDT"
      Given I create a TimeSeries in redis with key "BTCUSDT" and expiration "100000"
      Given I have a test-sample with key "BTCUSDT-json"
      When I add the first datapoint of the sample "BTCUSDT-json" to the ts named "BTCUSDT"
      Then the ts named "BTCUSDT" should have one datapoint
      And the datapoint payload should contain the following properties and values:
      """
      {
        "key": "BTCUSDT",
        "value": 6698.5,
        "tsms": 1585180700064
      }
      """

    Scenario: I add some datapoints to a brand new time serie
      Given I remove the ts "BTCUSDT"
      Given I create a TimeSeries in redis with key "BTCUSDT" and expiration "100000"
      Given I have a test-sample with key "BTCUSDT-json"
      When I add the first datapoint of the sample "BTCUSDT-json" to the ts named "BTCUSDT"
      Given I add 100 more datapoints of the sample "BTCUSDT-json" to the ts named "BTCUSDT"
      # we know there is duplication
      Then the ts named "BTCUSDT" should have "94" datapoints

    Scenario: I push all the datapoints to a new time serie
      Given I have a test-sample with key "BTCUSDT-json"
      Given I add all the datapoints of the sample "BTCUSDT-json" to the ts named "BTCUSDT"
      Then the ts named "BTCUSDT" should have more than "1000" datapoints

  Scenario: I request a historical data range and get a response I check against the sample
      Given I request second half of the sample "BTCUSDT-json" on the ts "BTCUSDT"
      Then every point should exist on the sample "BTCUSDT-json"
      # actually there's discrepancies: file stores ticks in 1/10 of millisecond precision and RedisTimeSeries an integer (millisecond)
      # the transformation is no longer injective ...

  Scenario: I push a full file content to store and want to aggregate data for every minute
    Given I remove the ts "BTCUSDT"
    Given I have a test-sample with key "BTCUSDT-json"
    And the ts named "BTCUSDT" exists and has no data
    Then I create a TimeSeries in redis with key "BTCUSDT" and expiration "100000"
    Given I add all the datapoints of the sample "BTCUSDT-json" to the ts named "BTCUSDT"
    Then I can request the entire range of the ts named "BTCUSDT" with an aggregation per minute
    And all the aggregations should have the same length
    # 1 min candle chart !
    And I should be able to create a new OHLC ts "BTCUSDT-60-OHLC"
    And I can export the OHLC to a csv file "btcusdt.csv" in the directory "var/data/csv/"