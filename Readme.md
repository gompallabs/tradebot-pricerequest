# Tradebot price-request svc 
Request data from a specific source and push formated TickData to datastore <br />
Then dispatch a msg for async processing to OHLCV or linear representation <br />
This service is *not aware of timeseries* <br />

Pre-requisites: <br />
    - PHP CLI >= v8.1 <br />
    - a crypto exchange account with a nice REST API (here we take Bybit as start point) <br />
    - a redis server with TimeSeries module extension <br />

Prepare: <br />
    1. Setup your API KEYS and REDIS connexion parameters as env vars <br />
    2. $ mkdir -p ./var/data/csv ./var/data/download/backup

Run: <br />
    - A cron script may help you setup < 30s periodic requests

Docker: <br />
    - $ make up