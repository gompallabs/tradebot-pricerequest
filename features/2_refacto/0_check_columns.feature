Feature: check we have all mandatory columns ### Check columns are here

  Background: I gather some tickData and check columns
    Given I have the tickData:
      | timestamp       | symbol    |   side  |   size    | price     | tickDirection |     trdMatchID                               |    grossValue      | homeNotional  | foreignNotional |
      | 1694023986.853	| BTCUSDT	|   Sell  |   0.002	  | 25787.6   | MinusTick	  |     7501bff7-9edf-5df6-8899-41c18a36dcbe	 |    5157520000	  | 0.002	      | 51.5752         |
      | 1694023986.853	| BTCUSDT	|   Sell  |   0.01	  | 25787.6	  | ZeroMinusTick | 	2c666574-db9c-5566-9876-de20956c76f5	 |    25787600000	  | 0.01	      | 257.876         |
      | 1694023986.854	| BTCUSDT	|   Sell  |   0.012	  | 25787.6	  | ZeroMinusTick | 	a4033372-4f1f-50f1-9cc8-78aba5996e41	 |    30945120000	  | 0.012	      | 309.4512        |
      | 1694023986.8593	| BTCUSDT	|   Sell  |   0.016	  | 25787.6	  | ZeroMinusTick | 	d05eb99b-6031-5f3c-bfa1-db361b5826e6	 |    41260160000	  | 0.016	      | 412.6016        |
      | 1694023986.8593	| BTCUSDT	|   Sell  |   0.034	  | 25787.6	  | ZeroMinusTick | 	ba593eec-3f8d-5286-8e35-4b8f172e6f9d	 |    87677840000	  | 0.034	      | 876.7784        |
      | 1694023986.8602	| BTCUSDT	|   Sell  |   0.05	  | 25787.6	  | ZeroMinusTick | 	85022184-7d60-5cf7-8aa3-55d63cc46745	 |    128938000000	  | 0.05	      | 1289.38         |
      | 1694023986.8616	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	8f4a6887-ef66-581d-968c-c1542f70bf65	 |    72205280000	  | 0.028	      | 722.0528        |

  Scenario:
    Given I extracted the data from "Bybit" exchange and from a csv "file" source
    Then I check the columns