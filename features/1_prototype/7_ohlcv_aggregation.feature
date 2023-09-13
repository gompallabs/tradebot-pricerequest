Feature:
  I may want to import tick data from csv file or make simple api call
  In order to know how to process this data, I need first to poke formats
  I want to aggregate the tickData as OHLC(V) timeseries.
  Some guidelines/ideas:
   - Aggregation is called after import of tickData and before push as a 1 second data to the store
   - Aggregation could in the future also be called to transform the 1 second data to any unit of time
   - Why 1 second ? just a tradeoff between keeping information persistent and having enough hardware to compute

  What parameters do I suppose are a good start, given previous tests ?
  -> Current RedisTimeSeries is a monocolumn structure and will reach significant length
  -> Maybe we can leverage labels and store each column as a ts

  Background: I gather some tickData and format to hydrate a TickData object
    Given I have the following tickData:
    | timestamp         | symbol    |   side  |   size    | price     | tickDirection |     trdMatchID                               |    grossValue      | homeNotional  | foreignNotional |
    | 1694023986.853	| BTCUSDT	|   Sell  |   0.002	  | 25787.6   | MinusTick	  |     7501bff7-9edf-5df6-8899-41c18a36dcbe	 |    5157520000	  | 0.002	      | 51.5752         |
    | 1694023986.853	| BTCUSDT	|   Sell  |   0.01	  | 25787.6	  | ZeroMinusTick | 	2c666574-db9c-5566-9876-de20956c76f5	 |    25787600000	  | 0.01	      | 257.876         |
    | 1694023986.854	| BTCUSDT	|   Sell  |   0.012	  | 25787.6	  | ZeroMinusTick | 	a4033372-4f1f-50f1-9cc8-78aba5996e41	 |    30945120000	  | 0.012	      | 309.4512        |
    | 1694023986.8593	| BTCUSDT	|   Sell  |   0.016	  | 25787.6	  | ZeroMinusTick | 	d05eb99b-6031-5f3c-bfa1-db361b5826e6	 |    41260160000	  | 0.016	      | 412.6016        |
    | 1694023986.8593	| BTCUSDT	|   Sell  |   0.034	  | 25787.6	  | ZeroMinusTick | 	ba593eec-3f8d-5286-8e35-4b8f172e6f9d	 |    87677840000	  | 0.034	      | 876.7784        |
    | 1694023986.8602	| BTCUSDT	|   Sell  |   0.05	  | 25787.6	  | ZeroMinusTick | 	85022184-7d60-5cf7-8aa3-55d63cc46745	 |    128938000000	  | 0.05	      | 1289.38         |
    | 1694023986.8616	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	8f4a6887-ef66-581d-968c-c1542f70bf65	 |    72205280000	  | 0.028	      | 722.0528        |
    | 1694023986.8616	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	f0bfce53-c887-5688-8eae-dd2b9bb81563	 |    72205280000	  | 0.028	      | 722.0528        |
    | 1694023986.8616	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	28d5a7a5-e6e7-5709-98bb-026885ef9cac	 |    72205280000	  | 0.028	      | 722.0528        |
    | 1694023986.8617	| BTCUSDT	|   Sell  |   0.028	  | 25787.6	  | ZeroMinusTick | 	e375a4c0-6260-5dc9-80cc-bf1a9296d40e	 |    72205280000	  | 0.028	      | 722.0528        |
    | 1694023986.8941	| BTCUSDT	|   Buy	  |   0.919	  | 25786.8   | MinusTick	  |     65aa0a26-ae20-520e-95e7-db9799cce8ed	 |    2369806920000	  | 0.919	      | 23698.0692      |
    | 1694023986.8967	| BTCUSDT	|   Sell  |   0.09	  | 25786.7   | MinusTick	  |     58d98d3d-44dd-57fa-ba8f-410340a37e7d	 |    232080300000	  | 0.09	      | 2320.803        |
    | 1694023986.9001	| BTCUSDT	|   Sell  |   0.03	  | 25785.8   | MinusTick	  |     b6fc9140-84b7-504d-98c0-0d7979559db3	 |    77357400000	  | 0.03	      | 773.574         |
    | 1694023986.9246	| BTCUSDT	|   Sell  |   0.002	  | 25784.9   | MinusTick	  |     875bfcee-b8e7-587a-afa3-a4b86e4820f0	 |    5156980000	  | 0.002	      | 51.5698         |
    | 1694023986.9246	| BTCUSDT	|   Sell  |   0.777	  | 25784.9	  | ZeroMinusTick | 	e9d1b8d6-3ea4-5924-aa63-d6ca2526eb42	 |    2003486730000	  | 0.777	      | 20034.8673      |
    | 1694023986.9247	| BTCUSDT	|   Sell  |   0.779	  | 25784.9	  | ZeroMinusTick | 	fc3262e5-8053-59e1-9679-5bde6380360c	 |    2008643710000	  | 0.779	      | 20086.4371      |
    | 1694023986.925	| BTCUSDT	|   Sell  |   0.273	  | 25784.9	  | ZeroMinusTick | 	888677c6-5d5c-5d89-87af-156db6f3c05f	 |    703927770000	  | 0.273	      | 7039.2777       |
    | 1694023986.9397	| BTCUSDT	|   Sell  |   0.031	  | 25784.7   | MinusTick	  |     a8cf45be-4615-57e9-9a6d-fea305e403ed	 |    79932570000	  | 0.031	      | 799.3257        |
    | 1694023986.9397	| BTCUSDT	|   Sell  |   0.002	  | 25784.5   | MinusTick	  |     8442750f-672b-52fb-96ec-6cd9c97b1a97	 |    5156900000	  | 0.002	      | 51.569          |
    | 1694023986.9397	| BTCUSDT	|   Sell  |   0.01	  | 25784.5	  | ZeroMinusTick | 	e8e11b5c-dc5f-5e6e-ad49-c69177d3653f	 |    25784500000	  | 0.01	      | 257.845         |
    | 1694023986.94	    | BTCUSDT	|   Sell  |   0.043	  | 25784.5	  | ZeroMinusTick | 	9ddcba85-c8c5-50c4-94a8-4dcce839f456	 |    110873350000	  | 0.043	      | 1108.7335       |
    | 1694023986.9459	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	46f95db7-b47e-5970-9b3d-51e646dc5573	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9459	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	db68e72d-e3ab-5648-bfb4-c16ceef61438	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9461	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	95e733d1-c58f-5de0-9696-91970dae5434	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9462	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	a4ad60cc-e43e-55e5-8c59-ac4158a09eaa	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9463	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	a6a47fb9-2f04-51d7-9cf0-8692c452d131	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9463	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	e347e436-d63f-58bb-8a48-5bf638144c7c	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9465	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	5bf9f863-8284-5f43-8858-2a68cdad5298	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9471	| BTCUSDT	|   Sell  |   0.033	  | 25784.5	  | ZeroMinusTick | 	c24bec2a-d79e-555f-bd31-324cafa6a6aa	 |    85088850000	  | 0.033	      | 850.8885        |
    | 1694023986.9541	| BTCUSDT	|   Sell  |   0.038	  | 25784.2   | MinusTick	  |     4d5230a1-da90-55d3-832d-3d2acf4303c2	 |    97979960000	  | 0.038	      | 979.7996        |
    | 1694023986.9546	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	b3349bf7-1df4-53b9-a420-c530418b80cf	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9549	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	8ff2fc98-d6c7-5ec5-93f4-6dc1b7c51a1c	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9549	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	5c2442e7-853b-504c-a668-3d6e286d9be0	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9551	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	50523104-a468-51d1-81a4-b445393201a6	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9551	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	196efe37-a020-55f5-b6f9-346f62fdb77f	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9552	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	93216d1f-fd18-5ad1-85b0-5a9810ad5f80	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9553	| BTCUSDT	|   Sell  |   0.1	  | 25784.2	  | ZeroMinusTick | 	0f562bce-cc34-542b-a6e6-237dc4bd02ea	 |    257842000000	  | 0.1	          | 2578.42         |
    | 1694023986.9553	| BTCUSDT	|   Sell  |   0.09	  | 25784.2	  | ZeroMinusTick | 	5e616d83-4416-5f3c-a441-f8e0b98ce178	 |    232057800000	  | 0.09	      | 2320.578        |
    | 1694023986.9562	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	e22f81ce-c4fc-5a92-8a55-559168f1f24b	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9562	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	7c52df25-618f-54c5-8c22-b69c89fbf638	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9562	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	b61b512b-c275-5ea4-9859-4cb95bd724a1	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9565	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	0458abb8-8113-5154-81f4-98376cec57b9	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9565	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	89c6343f-6431-5af9-85c4-f12a9e798fd4	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023986.9572	| BTCUSDT	|   Sell  |   0.171	  | 25784.2	  | ZeroMinusTick | 	22681617-ec4f-586e-9e5d-df69d3480351	 |    440909820000	  | 0.171	      | 4409.0982       |
    | 1694023987.0065	| BTCUSDT	|   Buy	  |   0.277	  | 25783.7   | MinusTick	  |     d6bf38d4-b54e-5539-9d93-1bcebe9e4ace	 |    714208490000	  | 0.277	      | 7142.0849       |
    | 1694023987.0422	| BTCUSDT	|   Sell  |   0.01	  | 25783     | MinusTick	  |     e7c8fb34-2b64-545c-a256-146342eb384f	 |    25783000000	  | 0.01	      | 257.83          |
    | 1694023987.0536	| BTCUSDT	|   Sell  |   0.003	  | 25782.1   | MinusTick	  |     0211a0fa-4d3d-5deb-9d31-0d8a40c84cbb	 |    7734630000	  | 0.003	      | 77.3463         |
    | 1694023987.0536	| BTCUSDT	|   Sell  |   0.024	  | 25782.1	  | ZeroMinusTick | 	00d28f13-a6ce-5a1c-8629-24d19402580a	 |    61877040000	  | 0.024	      | 618.7704        |
    | 1694023987.0573	| BTCUSDT	|   Sell  |   0.044	  | 25782.1	  | ZeroMinusTick | 	3f825f7d-20b9-513e-979a-6fa6d1dd37e9	 |    113441240000	  | 0.044	      | 1134.4124       |
    | 1694023987.0617	| BTCUSDT	|   Sell  |   0.044	  | 25782.1	  | ZeroMinusTick | 	910af03c-7eb9-5b5d-ac82-2dd312bcf94f	 |    113441240000	  | 0.044	      | 1134.4124       |
    | 1694023987.1903	| BTCUSDT	|   Sell  |   0.006	  | 25780.6   | MinusTick	  |     ec7ee2cf-3139-5509-84d7-fd41e368d2f6	 |    15468360000	  | 0.006	      | 154.6836        |
    | 1694023987.3862	| BTCUSDT	|   Sell  |   0.002	  | 25779.7   | MinusTick	  |     836f68a2-fa78-5b6d-9473-ae2dbc7b048f	 |    5155940000	  | 0.002	      | 51.5594         |
    | 1694023987.3862	| BTCUSDT	|   Sell  |   0.003	  | 25779.7	  | ZeroMinusTick | 	c5430fe4-8cdb-55cb-9fda-e00b3e25b293	 |    7733910000	  | 0.003	      | 77.3391         |
    | 1694023987.5057	| BTCUSDT	|   Sell  |   0.004	  | 25779.7	  | ZeroMinusTick | 	b1ca0f2d-413f-5337-be8d-dd8925f183bf	 |    10311880000	  | 0.004	      | 103.1188        |
    | 1694023987.5057	| BTCUSDT	|   Sell  |   0.004	  | 25779.7	  | ZeroMinusTick | 	13f89857-2085-5f20-8f20-51562c02f1cd	 |    10311880000	  | 0.004	      | 103.1188        |
    | 1694023987.5057	| BTCUSDT	|   Sell  |   0.322	  | 25779.7	  | ZeroMinusTick | 	b6859b59-be7f-5ad0-8c16-18ac7d1e4f9d	 |    830106340000	  | 0.322	      | 8301.0634       |
    | 1694023987.5072	| BTCUSDT	|   Buy	  |   0.009	  | 25779.8   | PlusTick      | 	c4ba4b7e-71a4-5ee0-bd74-31a3eb7702f7     |    23201820000	  | 0.009	      | 232.0182        |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.493	  | 25779.8	  | ZeroMinusTick | 	347048ea-b4b7-55c8-aa04-c469166e1877	 |    1270944140000	  | 0.493	      | 12709.4414      |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.202	  | 25779.8	  | ZeroMinusTick | 	e5ec5678-7712-5914-b7e6-80aafbd993c7	 |    520751960000	  | 0.202	      | 5207.5196       |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   1.002	  | 25779.8	  | ZeroMinusTick | 	90e374cf-b657-542a-9541-0a16e533371d	 |    2583135960000	  | 1.002	      | 25831.3596      |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.908	  | 25779.8	  | ZeroMinusTick | 	b3745acd-4474-5512-a37e-ac5610f96f54	 |    2340805840000	  | 0.908	      | 23408.0584      |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.36	  | 25779.8	  | ZeroMinusTick | 	9885aefd-69fb-5183-9f83-9b120f964868	 |    928072800000	  | 0.36	      | 9280.728        |
    | 1694023987.6853	| BTCUSDT	|   Buy	  |   0.035	  | 25779.8	  | ZeroMinusTick | 	4df7f715-ca6b-583e-af99-6155bbea1c4f	 |    90229300000	  | 0.035	      | 902.293         |
    | 1694023987.7067	| BTCUSDT	|   Buy	  |   0.612	  | 25779.8	  | ZeroMinusTick | 	ea6f8347-ca3e-5b65-9650-2b6ef9ea9a34	 |    1577723760000	  | 0.612	      | 15777.2376      |
    | 1694023987.7067	| BTCUSDT	|   Buy	  |   0.551	  | 25779.8	  | ZeroMinusTick | 	c1cf5f95-7160-5f8b-9490-9ae02dedf253	 |    1420466980000	  | 0.551	      | 14204.6698      |
    | 1694023987.7601	| BTCUSDT	|   Sell  |   0.004	  | 25779.7   | MinusTick	  |     540a8b60-891a-5c7e-897e-7cb03dbbb720	 |    10311880000	  | 0.004	      | 103.1188        |
    | 1694023987.8464	| BTCUSDT	|   Buy	  |   0.322	  | 25779.8   | PlusTick      | 	afa5d604-64b8-5030-be27-990d4ad3631f     |    830109560000	  | 0.322	      | 8301.0956       |
    | 1694023987.8621	| BTCUSDT	|   Buy	  |   0.001	  | 25779.8	  | ZeroMinusTick | 	788aa793-0aa1-5626-a930-9335b34564e6	 |    2577980000	  | 0.001	      | 25.7798         |
    | 1694023987.8621	| BTCUSDT	|   Buy	  |   0.001	  | 25780     | PlusTick      | 	14e262e0-75c1-56dd-bccf-d613a92a9146     |    2578000000	  | 0.001	      | 25.78           |
    | 1694023987.8621	| BTCUSDT	|   Buy	  |   0.001	  | 25781     | PlusTick      | 	b0dad40f-0020-5bc6-a502-23dff0d9ab9c     |    2578100000	  | 0.001	      | 25.781          |
    | 1694023987.9475	| BTCUSDT	|   Buy	  |   0.001	  | 25783.8   | PlusTick      | 	65563eff-37a5-57cc-b076-c11212680019     |    2578380000	  | 0.001	      | 25.7838         |
    | 1694023987.9686	| BTCUSDT	|   Buy	  |   0.073	  | 25783.8	  | ZeroMinusTick | 	261dc4c1-7cfe-5f75-b50b-83f6424d2f32	 |    188221740000	  | 0.073	      | 1882.2174       |
    | 1694023988.1256	| BTCUSDT	|   Sell  |   0.001	  | 25783.7   | MinusTick	  |     c5bc111d-8b9b-53b9-bf6f-cf0d32a2db36	 |    2578370000	  | 0.001	      | 25.7837         |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.399	  | 25783.7	  | ZeroMinusTick | 	2ea939dc-9ead-5696-aaa7-ae19aa76b63a	 |    1028769630000	  | 0.399	      | 10287.6963      |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.4	  | 25783.7	  | ZeroMinusTick | 	73f45879-b35b-5add-89dd-20a40fa90441	 |    1031348000000	  | 0.4	          | 10313.48        |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.4	  | 25783.7	  | ZeroMinusTick | 	31004d73-634b-51f7-9777-5b0afa10e714	 |    1031348000000	  | 0.4	          | 10313.48        |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.4	  | 25783.7	  | ZeroMinusTick | 	388e769d-ced9-5876-b6fd-3938b4c50641	 |    1031348000000	  | 0.4	          | 10313.48        |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.4	  | 25783.7	  | ZeroMinusTick | 	b765e88b-80a2-5aee-b72d-a2f3230317ef	 |    1031348000000	  | 0.4	          | 10313.48        |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.386	  | 25783.7	  | ZeroMinusTick | 	41cf2db3-184e-5c1f-bdd9-8fd70c00f180	 |    995250820000	  | 0.386	      | 9952.5082       |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.894	  | 25783.7	  | ZeroMinusTick | 	ef15ed94-40ef-56d8-a8f3-4d52f65e415a	 |    2305062780000	  | 0.894	      | 23050.6278      |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.001	  | 25783.7	  | ZeroMinusTick | 	4f8022ba-5fba-5e06-996d-96d4708e6cb4	 |    2578370000	  | 0.001	      | 25.7837         |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.583	  | 25780.9   | MinusTick	  |     45606c95-f595-5162-86bf-458d76e382a7	 |    1503026470000	  | 0.583	      | 15030.2647      |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.116	  | 25780.9	  | ZeroMinusTick | 	121b8601-2774-57df-bd6d-855aa43bd514	 |    299058440000	  | 0.116	      | 2990.5844       |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.077	  | 25780.9	  | ZeroMinusTick | 	167a6191-da94-5e2a-8cc7-1dd89ab518bf	 |    198512930000	  | 0.077	      | 1985.1293       |
    | 1694023988.1465	| BTCUSDT	|   Sell  |   0.388	  | 25780.9	  | ZeroMinusTick | 	ddd061f1-faf6-5242-880d-d4eaf21101cc	 |    1000298920000	  | 0.388	      | 10002.9892      |
    | 1694023988.1503	| BTCUSDT	|   Sell  |   0.674	  | 25779.7   | MinusTick	  |     1c73f025-046f-5192-9c67-b019a995934b	 |    1737551780000	  | 0.674	      | 17375.5178      |
    | 1694023988.1503	| BTCUSDT	|   Sell  |   0.023	  | 25779.7	  | ZeroMinusTick | 	60792e40-2e70-5e07-a0f9-816024dd7bf2	 |    59293310000	  | 0.023	      | 592.9331        |
    | 1694023988.167	| BTCUSDT	|   Sell  |   0.01	  | 25779.7	  | ZeroMinusTick | 	f0aa21bc-be20-5377-8374-92abf7bc98db	 |    25779700000	  | 0.01	      | 257.797         |
    | 1694023988.1814	| BTCUSDT	|   Sell  |   0.063	  | 25779.7	  | ZeroMinusTick | 	cb373826-a2ef-51a9-aa61-b5c3731b8f8b	 |    162412110000	  | 0.063	      | 1624.1211       |
    | 1694023988.1814	| BTCUSDT	|   Sell  |   0.057	  | 25779.7	  | ZeroMinusTick | 	729d82e9-7362-5d20-9319-196b9fa8910d	 |    146944290000	  | 0.057	      | 1469.4429       |
    | 1694023988.1816	| BTCUSDT	|   Sell  |   0.072	  | 25779.4   | MinusTick	  |     24e667ca-6083-59d1-9a60-6e0eb4e8d2d8	 |    185611680000	  | 0.072	      | 1856.1168       |
    | 1694023988.3488	| BTCUSDT	|   Buy	  |   0.003	  | 25775.7   | MinusTick	  |     8e55177d-7389-52e3-ae47-75c3671ae056	 |    7732710000	  | 0.003	      | 77.3271         |
    | 1694023988.5692	| BTCUSDT	|   Sell  |   0.077	  | 25775.6   | MinusTick	  |     5608a737-e904-52ab-a61e-b8b19fcd3dcd	 |    198472120000	  | 0.077	      | 1984.7212       |
    | 1694023988.6432	| BTCUSDT	|   Sell  |   0.015	  | 25775.6	  | ZeroMinusTick | 	3c733744-902c-5492-b214-fd65aced317b	 |    38663400000	  | 0.015	      | 386.634         |
    | 1694023988.649	| BTCUSDT	|   Sell  |   0.066	  | 25775.6	  | ZeroMinusTick | 	ee97b619-bb4a-5c66-a25f-9511c2078655	 |    170118960000	  | 0.066	      | 1701.1896       |
    | 1694023988.758	| BTCUSDT	|   Sell  |   0.17	  | 25775.6	  | ZeroMinusTick | 	29a0e28f-263e-5863-b69e-d407f27fc03f	 |    438185200000	  | 0.17	      | 4381.852        |
    | 1694023988.7822	| BTCUSDT	|   Sell  |   0.07	  | 25775.6	  | ZeroMinusTick | 	ec4b7bf9-cd3b-5cd2-bac3-1c6a59ed8705	 |    180429200000	  | 0.07	      | 1804.292        |
    | 1694023989.1922	| BTCUSDT	|   Buy	  |   0.003	  | 25775.7   | PlusTick      | 	a0ce327d-e733-5919-bd84-e7aa52e1ffbe     |    7732710000	  | 0.003	      | 77.3271         |
    | 1694023989.288	| BTCUSDT	|   Buy	  |   0.001	  | 25775.7	  | ZeroMinusTick | 	beef2d1d-6288-5520-b125-09b8b06c31a9	 |    2577570000	  | 0.001	      | 25.7757         |
    | 1694023989.4181	| BTCUSDT	|   Buy	  |   0.068	  | 25775.7	  | ZeroMinusTick | 	50ff9efc-39da-5626-b0bd-89fc6273f6aa	 |    175274760000	  | 0.068	      | 1752.7476       |
    | 1694023989.4848	| BTCUSDT	|   Buy	  |   0.106	  | 25775.7	  | ZeroMinusTick | 	4bee3ab1-7d5e-58fb-8432-08e8a5af1b39	 |    273222420000	  | 0.106	      | 2732.2242       |
    | 1694023989.4851	| BTCUSDT	|   Buy	  |   0.219	  | 25775.7	  | ZeroMinusTick | 	25b8121f-1ff6-5a7b-a53a-0376f5c3891d	 |    564487830000	  | 0.219	      | 5644.8783       |
    | 1694023989.4851	| BTCUSDT	|   Buy	  |   0.4	  | 25775.7	  | ZeroMinusTick | 	5f5bf77f-e058-5028-ad30-502e70658282	 |    1031028000000	  | 0.4	          | 10310.28        |
    | 1694023989.4851	| BTCUSDT	|   Buy	  |   0.4	  | 25775.7	  | ZeroMinusTick | 	a0782004-d406-5d9c-8a14-c0bcc89b458f	 |    1031028000000	  | 0.4	          | 10310.28        |
    | 1694023989.4851	| BTCUSDT	|   Buy	  |   0.361	  | 25775.7	  | ZeroMinusTick | 	f9a8004b-0df6-594f-8848-7790e1fb75e3	 |    930502770000	  | 0.361	      | 9305.0277       |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.039	  | 25775.7	  | ZeroMinusTick | 	a8920d0e-b603-5d86-9dab-4c46357f9a6f	 |    100525230000	  | 0.039	      | 1005.2523       |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.4	  | 25775.7	  | ZeroMinusTick | 	8c088a8e-48a3-5714-84e1-bdfb8eb44316	 |    1031028000000	  | 0.4	          | 10310.28        |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.4	  | 25775.7	  | ZeroMinusTick | 	7b784668-0add-5f27-ba2d-12347794e04f	 |    1031028000000	  | 0.4	          | 10310.28        |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.087	  | 25775.7	  | ZeroMinusTick | 	d48cf0c9-0a3b-5b07-9191-1191e3b24410	 |    224248590000	  | 0.087	      | 2242.4859       |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.001	  | 25775.7	  | ZeroMinusTick | 	24c87011-56b4-5c05-a95e-f36755bca89f	 |    2577570000	  | 0.001	      | 25.7757         |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.024	  | 25775.7	  | ZeroMinusTick | 	71e50e5c-103c-516f-9958-d3f3fc65b623	 |    61861680000	  | 0.024	      | 618.6168        |
    | 1694023989.4858	| BTCUSDT	|   Buy	  |   0.039	  | 25775.7	  | ZeroMinusTick | 	d34901e3-60c5-5806-82a6-9c7b1342acef	 |    100525230000	  | 0.039	      | 1005.2523       |
    | 1694023989.4861	| BTCUSDT	|   Buy	  |   0.466	  | 25777.8   | PlusTick      | 	80111f78-47a8-5bd9-9ecb-f057658879c8     |    1201245480000	  | 0.466	      | 12012.4548      |
    | 1694023989.5086	| BTCUSDT	|   Buy	  |   0.108	  | 25779.7   | PlusTick      | 	98aa60b2-032f-5075-870a-b1b899f0e935     |    278420760000	  | 0.108	      | 2784.2076       |
    | 1694023989.519	| BTCUSDT	|   Buy	  |   0.001	  | 25779.7	  | ZeroMinusTick | 	b105ad43-441f-5e4f-979a-5af94b645d77	 |    2577970000	  | 0.001	      | 25.7797         |
    | 1694023989.6109	| BTCUSDT	|   Buy	  |   0.09	  | 25779.7	  | ZeroMinusTick | 	39902117-2e23-51b2-b830-9f7d96277a57	 |    232017300000	  | 0.09	      | 2320.173        |
    | 1694023989.6222	| BTCUSDT	|   Buy	  |   0.001	  | 25780     | PlusTick      | 	eba64290-5f08-52bf-91dc-44d662712eff     |    2578000000	  | 0.001	      | 25.78           |
    | 1694023989.7032	| BTCUSDT	|   Buy	  |   0.001	  | 25782.7   | PlusTick      | 	f9a66758-95b3-5b5d-9b98-237089c7f5f4     |    2578270000	  | 0.001	      | 25.7827         |
    | 1694023989.7822	| BTCUSDT	|   Sell  |   0.007	  | 25782.6   | MinusTick	  |     8217aa77-f23e-5388-8a9a-c1fb2b3a2dfa	 |    18047820000	  | 0.007	      | 180.4782        |
    | 1694023989.8638	| BTCUSDT	|   Buy	  |   0.002	  | 25782.8	  | PlusTick	  |     a08ff5c1-33cd-511e-a8b7-bf10ed1da8de	 |    5156560000      | 0.002	      | 51.5656         |
    | 1694023990.1123	| BTCUSDT	|   Buy	  |   0.095	  | 25783.1	  | PlusTick	  |     d05b1945-199e-54f7-9b88-86c1db9367ad	 |    244939450000    | 0.095	      | 2449.3945       |
    | 1694023990.1151	| BTCUSDT	|   Buy	  |   0.005	  | 25783.1	  | ZeroMinusTick |	    afdc31ce-8257-5f21-a4f0-a3ee30ccee21	 |    12891550000     | 0.005	      | 128.9155        |
    | 1694023990.1151	| BTCUSDT	|   Buy	  |   0.002	  | 25786	  | PlusTick	  |     89c0132c-aa76-54ab-9aa6-75b2a396fbc0	 |    5157200000	  | 0.002	      | 51.572          |
    | 1694023990.1151	| BTCUSDT	|   Buy	  |   0.054	  | 25786.2	  | PlusTick	  |     54afeaa7-fc52-5262-b928-3b9d93fdfbbe	 |    139245480000    | 0.054	      |  1392.4548      |
    | 1694023990.247	| BTCUSDT	|   Buy	  |   0.232	  | 25786.2	  | ZeroMinusTick |	    212e3576-8d93-5218-9bd5-8e295ca85d5a	 |    598239840000    | 0.232	      |  5982.3984      |
    | 1694023990.3269	| BTCUSDT	|   Sell  |	  0.001	  | 25786.1	  | MinusTick	  |     a7ecb07c-077d-5c27-a11b-659e1dd679f4	 |    2578610000	  | 0.001	      |  25.7861        |
    | 1694023990.3974	| BTCUSDT	|   Buy	  |   0.142	  | 25786.2	  | PlusTick	  |     b4ec8d8b-cee9-58d2-9d9d-acd6609e206e	 |    366164040000    | 0.142	      |  3661.6404      |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  0.201	  | 25786.1	  | MinusTick	  |     b35990a5-ab2b-5299-878a-dd3660d02c59	 |    518300610000    | 0.201	      |  5183.0061      |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  0.202	  | 25786.1	  | ZeroMinusTick |	    11d12795-1a3f-549c-b01f-35fdddf27bf8	 |    520879220000    | 0.202	      |  5208.7922      |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  0.1	  | 25786.1	  | ZeroMinusTick |	    6686a82c-43bf-50c2-889c-ffa2c370f20d	 |    257861000000    | 0.1	          |  2578.61        |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  1.2	  | 25786.1	  | ZeroMinusTick |	    eb61123b-5861-5b9a-8d90-d978001f1a8c	 |    3094332000000   | 1.2	          |  30943.32       |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  0.2	  | 25786.1	  | ZeroMinusTick |	    c8178b54-73fe-5cfd-af8e-f18bf0056566	 |    515722000000    | 0.2	          |  5157.22        |
    | 1694023990.4319	| BTCUSDT	|   Sell  |	  0.001	  | 25786.1	  | ZeroMinusTick |	    831b2bf4-0a0e-5192-ab29-a31163ff6764	 |    2578610000	  | 0.001	      |  25.7861        |
    | 1694023990.471	| BTCUSDT	|   Sell  |	  0.005	  | 25783.2	  | MinusTick	  |     9ded20e1-9442-57c0-8590-21cc7cc6b822	 |    12891600000	  | 0.005	      |  128.916        |
    | 1694023990.5823	| BTCUSDT	|   Sell  |	  0.001	  | 25783	  | MinusTick	  |     aa20ed35-0e18-5bd8-96f2-f697c3b9713f	 |    2578300000	  | 0.001	      |  25.783         |
    | 1694023990.7156	| BTCUSDT	|   Sell  |	  0.087	  | 25783	  | ZeroMinusTick |	    1f2e2e11-5b6f-588b-9df6-d4ffb94aad13	 |    224312100000	  | 0.087	      |  2243.121       |
    | 1694023990.7156	| BTCUSDT	|   Sell  |	  0.01	  | 25782.9	  | MinusTick	  |     661271b6-af74-58f0-b3d3-078921694ed9	 |    25782900000	  | 0.01	      |  257.829        |
    | 1694023990.7156	| BTCUSDT	|   Sell  |	  0.903	  | 25782.3	  | MinusTick	  |     0a710e96-53e8-52f4-bba0-69976da43855	 |    2328141690000	  | 0.903	      |  23281.4169     |
    | 1694023990.8632	| BTCUSDT	|   Buy	  |   0.309	  | 25779.6	  | MinusTick	  |     a49ee003-c41b-50f1-aa18-28db7d9931dc	 |    796589640000	  | 0.309	      |  7965.8964      |
    | 1694023990.8997	| BTCUSDT	|   Sell  |	  0.425	  | 25779.5	  | MinusTick	  |     9940d15d-f7a7-5ace-9351-a560dc648311	 |    1095628750000	  | 0.425	      |  10956.2875     |
    | 1694023990.9677	| BTCUSDT	|   Sell  |	  0.001	  | 25779.5	  | ZeroMinusTick |	    3f4be922-b4fc-501b-a931-906402a083c5	 |    2577950000	  | 0.001	      |  25.7795        |
    | 1694023991.0449	| BTCUSDT	|   Buy	  |   0.091	  | 25779.6	  | PlusTick	  |     64297e63-3b3c-5410-bf18-5fafb53dcecd	 |    234594360000	  | 0.091	      |  2345.9436      |

  Scenario: I execute preparation to have proper raws
    Given I should check if the mandatory columns are here
    Then I should remove non mandatory columns
    And I should guess the timestamp format
    And I should sort "timestamp" in "ASC" order
    And I should have "142" samples

  Scenario:
    Given I download the "1" "last" files on "https://public.bybit.com" at the slug "/trading/BTCUSDT" in the "var/data/download/" directory
    And I parse the files
    Then I aggregate the tick data with a "1" second step and push it to datastore under the key "btc-usdt-1"

  Scenario:
    Given I download the "1" "last" files on "https://public.bybit.com" at the slug "/trading/BTCUSDT" in the "var/data/download/" directory
    And I parse the files
    Then I aggregate the tick data with a "10" second step and push it to datastore under the key "btc-usdt-2"
