Feature: Download files and uncompress them

  Scenario:
    Given I browse the url "https://public.bybit.com" at the slug "/trading/BTCPERP" with the dom crawler
    Then I can list the files
    And I download the first file of the list in the "var/data/download/" directory
    Then I decompress the file
    And I should parse the csv file

