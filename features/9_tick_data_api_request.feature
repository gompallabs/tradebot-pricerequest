Feature: I request recent-trades api in order to have last 500 trades (tick data)
  The data must be aggregated to existing ts imported daily from files
  So I will update the overlapping last seconds of records only, to join the ts
  After the ts are complete and continuously synchronized, I will not import daily files

  Background:
    Given I have a Bybit api client ready

  Scenario: I request recent-trades
    Given I request "BTCUSDT" recent-trades from "Bybit" exchange
    And I aggregate the tick data with a "1" second step
    Then the aggregate should contain all the open, high, low, close, buyVolume, sellVolume series
    And each split serie should have the same number of elements

  Scenario: I request recent-trades and push to ts
    Given I request "BTCUSDT" recent-trades from "Bybit" exchange
    And I aggregate the tick data with a "1" second step
    And each split serie should have the same number of elements
    Then I push the series to datastore
    # here before pushing, we should check the overlap with existing series
    # i.e. request the last second in the store and compare
    # if values are different hum maybe we should update :)
