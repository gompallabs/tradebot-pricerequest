Feature:
  I send a price request on the Api of Bybit broker and I parse the response

  Background:
    Given I have a "rest api" client with relevant api keys for "Bybit" source

  Scenario: It request price of bitcoin perpetual future
    When I send a request to the "price" route of "Bybit" broker for ticker "BTCUSDT"
    Then the response should be received
    And the response should be valid
    And the response should contain an array with time an price key-value data

  Scenario: It request historical prices of bitcoin perpetual future from a start and end date
    When   I send a request to the "price_history" route of "Bybit" broker for ticker "BTCUSDT" with parameters:
        """
        {
            "from": "2023-05-29 13:00:00",
            "to": "2023-07-30 16:42:00"
        }
        """
    Then the response should be received
    And the response should be valid
    And the response should contain an array of arrays with time an price key-value data