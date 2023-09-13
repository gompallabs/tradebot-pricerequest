Feature:
  I use a crawler to list all files available for a random coin download's page of Bybit
  Parse some of the files and store their content in datastore
  Test some features of RedisTimeSeries module

  Background:
    Given I browse the url "https://public.bybit.com" at the slug "/trading/DODOUSDT" with the dom crawler
    Then I can list the files
    And I download the first file of the list in the "var/data/download/" directory
    Then I decompress the file
    And I should parse the csv file
    And I want to parse the file to a simple array with time as key and price as value
    And I store the data into Redis under the key "test-sample"
    And I have a test-sample

  Scenario: poke ts.info function
    Given I remove the ts "ts-test"
    And I request ts info and have no ts named "ts-test"
    Given I create a TimeSeries in redis with with key "ts-test" with expiration "600" seconds
    And I request ts info with key "ts-test"
    Then the payload should contain a "retentionTime" key with "600000" value
    And the payload should contain a "lastTimestamp" key with "0" timestamp value
    And the payload should contain a "labels" key with an empty array value
    And the payload should contain a "rules" key with an empty array value
    And the payload should contain a "sourceKey" key with a "null" value


  Scenario: test the ts.create function by adding data
    Given I remove the ts "ts-test"
    Given I create a TimeSeries in redis with with key "ts-test" with expiration "600" seconds
    And I add the oldest data point of the test-sample key to the "ts-test" ts
    Then I should have a datapoint in ts "ts-test"
    And the datapoint payload should contain the following properties and values:
    """
    {
      "key": "ts-test",
      "value": 0.41645,
      "tsms": 1650616204000
    }
    """

