Feature: transform raw trades history (tickData) to OHLCV
### TickDataTransformer

  Background: I gather some tickData and check columns
    Given I have the tickData:
      | timestamp       | symbol    |   side  |   size    | price     | tickDirection |     trdMatchID                               |    grossValue      | homeNotional  | foreignNotional |
      | 1694023986.853	| BTCUSDT	|   Sell  |   0.002	  | 25787.6   | MinusTick	  |     7501bff7-9edf-5df6-8899-41c18a36dcbe	 |    5157520000	  | 0.002	      | 51.5752         |
      | 1694023986.853	| BTCUSDT	|   Sell  |   0.01	  | 25787.6	  | ZeroMinusTick | 	2c666574-db9c-5566-9876-de20956c76f5	 |    25787600000	  | 0.01	      | 257.876         |
      | 1694023986.854	| BTCUSDT	|   Sell  |   0.012	  | 25787.6	  | ZeroMinusTick | 	a4033372-4f1f-50f1-9cc8-78aba5996e41	 |    30945120000	  | 0.012	      | 309.4512        |
      | 1694023986.8617	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	e375a4c0-6260-5dc9-80cc-bf1a9296d40e	 |    72205280000	  | 0.028	      | 722.0528        |
      | 1694023986.8941	| BTCUSDT	|   Buy	  |   0.919	  | 25786.8   | MinusTick	  |     65aa0a26-ae20-520e-95e7-db9799cce8ed	 |    2369806920000	  | 0.919	      | 23698.0692      |
      | 1694023986.8967	| BTCUSDT	|   Sell  |   0.09	  | 25786.7   | MinusTick	  |     58d98d3d-44dd-57fa-ba8f-410340a37e7d	 |    232080300000	  | 0.09	      | 2320.803        |
      | 1694023986.9001	| BTCUSDT	|   Sell  |   0.03	  | 25785.8   | MinusTick	  |     b6fc9140-84b7-504d-98c0-0d7979559db3	 |    77357400000	  | 0.03	      | 773.574         |
      | 1694023986.925	| BTCUSDT	|   Sell  |   0.273	  | 25784.9	  | ZeroMinusTick | 	888677c6-5d5c-5d89-87af-156db6f3c05f	 |    703927770000	  | 0.273	      | 7039.2777       |
      | 1694023986.9463	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	a6a47fb9-2f04-51d7-9cf0-8692c452d131	 |    85088850000	  | 0.033	      | 850.8885        |
      | 1694023986.9471	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	c24bec2a-d79e-555f-bd31-324cafa6a6aa	 |    85088850000	  | 0.033	      | 850.8885        |
      | 1694023986.9549	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	8ff2fc98-d6c7-5ec5-93f4-6dc1b7c51a1c	 |    440909820000	  | 0.171	      | 4409.0982       |
      | 1694023986.9552	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	93216d1f-fd18-5ad1-85b0-5a9810ad5f80	 |    440909820000	  | 0.171	      | 4409.0982       |
      | 1694023986.9565	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	89c6343f-6431-5af9-85c4-f12a9e798fd4	 |    440909820000	  | 0.171	      | 4409.0982       |
      | 1694023987.0065	| BTCUSDT	|   Buy	  |   0.277	  | 25783.7   | MinusTick	  |     d6bf38d4-b54e-5539-9d93-1bcebe9e4ace	 |    714208490000	  | 0.277	      | 7142.0849       |
      | 1694023987.0422	| BTCUSDT	|   Sell  |   0.01	  | 25783     | MinusTick	  |     e7c8fb34-2b64-545c-a256-146342eb384f	 |    25783000000	  | 0.01	      | 257.83          |
      | 1694023987.0536	| BTCUSDT	|   Sell  |   0.003	  | 25782.1   | MinusTick	  |     0211a0fa-4d3d-5deb-9d31-0d8a40c84cbb	 |    7734630000	  | 0.003	      | 77.3463         |
      | 1694023987.0573	| BTCUSDT	|   Sell  |   0.044	  | 25782.1	  | ZeroMinusTick | 	3f825f7d-20b9-513e-979a-6fa6d1dd37e9	 |    113441240000	  | 0.044	      | 1134.4124       |
      | 1694023987.0617	| BTCUSDT	|   Sell  |   0.044	  | 25782.1	  | ZeroMinusTick | 	910af03c-7eb9-5b5d-ac82-2dd312bcf94f	 |    113441240000	  | 0.044	      | 1134.4124       |
      | 1694023987.1903	| BTCUSDT	|   Sell  |   0.006	  | 25780.6   | MinusTick	  |     ec7ee2cf-3139-5509-84d7-fd41e368d2f6	 |    15468360000	  | 0.006	      | 154.6836        |
      | 1694023987.3862	| BTCUSDT	|   Sell  |   0.002	  | 25779.7   | MinusTick	  |     836f68a2-fa78-5b6d-9473-ae2dbc7b048f	 |    5155940000	  | 0.002	      | 51.5594         |
      | 1694023987.5057	| BTCUSDT	|   Sell  |   0.004	  | 25779.7	  | ZeroMinusTick | 	13f89857-2085-5f20-8f20-51562c02f1cd	 |    10311880000	  | 0.004	      | 103.1188        |
      | 1694023987.5072	| BTCUSDT	|   Buy	  |   0.009	  | 25779.8   | PlusTick      | 	c4ba4b7e-71a4-5ee0-bd74-31a3eb7702f7     |    23201820000	  | 0.009	      | 232.0182        |
      | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.035	  | 25779.8	  | ZeroMinusTick | 	4df7f715-ca6b-583e-af99-6155bbea1c4f	 |    90229300000	  | 0.035	      | 902.293         |


    Given I extracted the data from "Bybit" exchange and from a csv "file" source
    Then I check the columns

  Scenario:
    Given I use the DataProcessor
    Then the tickData should contain "2" PriceOhlcv objects
