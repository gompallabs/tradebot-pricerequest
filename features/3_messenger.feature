Feature:
  Download file and push a msg to Processing queue
  Request api recent_trades and push a msg

  Scenario:
    Given I download the "1" first file for "spot" instrument "BTCUSDT" of "Bybit" page
    Then a "FileDownloadedEvent" event should be dispatched to the "FileProcessing" queue
    And the event should contain the exchange name "Bybit"
    And the event should contain a coin array with data:
    """
    {
      "ticker": "BTCUSDT",
      "category": "spot"
    }
    """
    And the event should contain a File entity with properties name, extension and path

  Scenario:
    Given I request the "recent_trades" for "spot" instrument "BTCUSDT" of "Bybit" exchange
    Then a "ApiRequestEvent" event should be dispatched to the "FileProcessing" queue
    And the event should contain a coin array with data:
    """
    {
      "ticker": "BTCUSDT",
      "category": "spot"
    }
    """
    And the event should contain the exchange name "Bybit"
    And the event should contain an array of arrays with the following structure of keys:
    """
    {
      "timestamp": "number",
      "price": "number",
      "size": "number",
      "side": "chars"
    }
    """

