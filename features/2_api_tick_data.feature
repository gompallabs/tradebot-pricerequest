Feature: Request recent trades and push a msg

  Background:
    Given I have a Bybit api client ready

  Scenario:
    Given I send a request to the "recent_trade" route of "Bybit" broker for ticker "BTCUSDT" of category "spot"
    Then the response should have the following structures of keys:
    """
    {
        "price": "25813.00",
        "size": "0.054",
        "side": "Buy",
        "timestamp": "1692734450916"
    }
    """
