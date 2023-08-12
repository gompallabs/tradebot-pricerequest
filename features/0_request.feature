Feature:
    I send a few get request on the Api of Bybit broker to see if there is a response

    Background:
        Given I have a "rest api" client with relevant api keys for "Bybit" source

    Scenario: It request price of bitcoin perpetual future
        When I send a request to the "price" route of "Bybit" broker for ticker "BTCUSDT"
        Then the response should be received

    Scenario: It request historical prices of bitcoin perpetual future from a start and end date
        When   I send a request to the "price_history" route of "Bybit" broker for ticker "BTCUSDT" with parameters:
        """
        {
            "from": "2023-05-29 13:00:00",
            "to": "2023-07-30 16:42:00"
        }
        """
        Then the response should be received

    Scenario: It requests recent trades of "BTCUSDT" in category "linear"
        Given I send a request to the "recent_trade" route of "Bybit" broker for ticker "BTCUSDT"
        Then the response should be received
        And the response should be an array of the following structures of keys:
        """
        {
            "price": "25813.00",
            "size": "0.054",
            "side": "Buy",
            "timestamp": "1692734450916"
        }
        """
        And the time of last trade should be close to current time with a maximum "10" second delta
        And I can aggregate by summing volumes of the trades that occurs in the same millisecond
