<?php

class RamblerHoroscopeParser {

    private $url;
    private $signs;
    private $query;

    /**
     * Sets default values to the "url" and "signs" fields
     */
    function __construct() {
        $this->url   = 'http://horoscopes.rambler.ru/index.html';
        $this->signs = array('aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces');
    }

    /**
     * Gets horoscope by month
     *
     * @param $url  - url for parsing
     * @param $sign - one sign from array $this->signs
     * @param $date - required date
     */
    private function parseMonth($url, $sign, $date) {

        foreach ($sign as $s) {

            // create curl and add url to this one
            $ch = curl_init($url.'?sign='.$s.'&date='.$date.'-01&type=monthly');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl_scraped_page = curl_exec($ch);

            // initialize simple HTML DOM instance
            $html = new simple_html_dom();
            $html->load($curl_scraped_page);

            // check if element with forecast exist
            if ($html->find('div p', 0)) {
                $res[$s]['text']    = $html->find('div p', 0)->plaintext;
                $res[$s]['date']    = $html->find('div font b i', 0)->plaintext;
                $res[$s]['image']   = file_get_contents($url.$html->find('div.sign-icon__container img', 0)->src);
                $this->prepareQuery($s, $res[$s]);
            } else {
                printf("Horoscope not found\n");
            }
        }
    }

    /**
     * Gets horoscope by year
     *
     * @param $url
     * @param $sign
     * @param $date
     */
    private function parseYear($url, $sign, $date) {

        for ($i = 1; $i <= 12; $i ++) {
            $this->parseMonth($url, $sign, $date.'-'.sprintf('%02d', $i));
        }
    }

    /**
     * Creates query string for saving to the DB
     *
     * @param $sign
     * @param $res
     */
    private function prepareQuery($sign, $res) {

        // create a huge "multi query" string
        $this->query .= ' INSERT INTO signs(sign, logo)
                          VALUES ("'.$sign.'", "'.addslashes($res['image']).'")
                          ON DUPLICATE KEY UPDATE logo = "'.addslashes($res['image']).'";
                          SET @var = (SELECT id FROM signs WHERE sign LIKE "'.$sign.'");
                          INSERT INTO forecasts (signId, forecast, forecastDate)
                          VALUES (@var, "'.$res['text'].'", "'.$res['date'].'")
                          ON DUPLICATE KEY UPDATE forecast = "'.$res['text'].'"; ';
    }

    /**
     * Executes query which is created in prepareQuery method
     */
    private function executeQuery() {

        // connect to the DB
        $sql = new mysqli("localhost","root","123qwe","horoscope");
        $sql->set_charset("utf8");

        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit;
        }

        // check if we have connection and try to write the data to the DB
        if ($sql->multi_query($this->query)) {
            printf("success\n");
        } else {
            printf("failed\n");
            printf($sql->error);
        }

        // close connection in any case
        $sql->close();
    }

    /**
     * Checks if we have required parameters and execute parsing mechanism if they are correct
     *
     * @param $sign
     * @param $date
     * @param $type
     */
    function parse($sign, $date, $type) {

        if ($sign == 'all') {
            $sign = $this->signs;
        } else {
            $sign = array($sign);
        }

        if ($type == 'm') {
            $this->parseMonth($this->url, $sign, $date);
        } elseif ($type == 'a') {
            $this->parseYear($this->url, $sign, $date);
        }

        $this->executeQuery();
    }
}
