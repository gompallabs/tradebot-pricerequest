# Tradebot price-request svc 
Request data from a specific source and push a msg to a queue for async worker processing<br />
This service is *not aware of timeseries* and *do not parse any data* <br />

Pre-requisites: <br />
    - PHP CLI >= v8.1<br />
    - a broker/exchange account with a nice REST API (here we take Bybit)<br />
    - a RabbitMQ instance<br />

Prepare: <br />
    1. Setup your API KEYS as env vars <br />
    2. $ mkdir -p ./var/data/csv ./var/data/download/backup

Run: <br />
    - A cron script may help you setup < 30s periodic requests

Docker: <br />
    - $ make up