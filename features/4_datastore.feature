Feature:
  I test the datastore with a test key
  I should be able to retrieve the test key
  I send a value with a key with a duration of 1.6s
  I sleep for 1s and should retrieve the key
  I sleep for 0.7s and should not retrieve the key

  Background:
    Given I have a redis data store up

  Scenario: I make a fake request and push the data to redis. I test if expiration works
    Given I start the chronometer
    And I push the test key "test" to redis under that expires after 1.6 seconds
    And I stop the chronometer
    Then I wait 1 seconds and can retrieve the key "test" and it has the value "test"
    And the insert should have lasted less than 10 ms
