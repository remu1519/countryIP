<?php

class CountryIP
{
    const SUBNET_BASE = 0b11111111111111111111111111111111;

    /**
     * IPリストを取得してくるURL
     *
     * @var string
     */
    private $_ipListUrl = "ftp://ftp.apnic.net/pub/stats/apnic/delegated-apnic-extended-latest";

    /**
     * CURLの追加オプション
     *
     * @var array
     */
    private $_addCurlOptionLisdt = [];

    /**
     * 全IPリストを保持
     *
     * @var array|null
     */
    private $_allIpStrList = null;

    /**
     *
     * @param string $url
     */
    public function __construct($url = null)
    {
        if ( ! is_null($url)) {
            $this->_ipListUrl = $url;
        }
    }

    /**
     * IPリストを取得するURL変更
     *
     * @param string $url
     */
    public function setIpListUrl($url)
    {
        $this->_ipListUrl = $url;
    }

    /**
     * IPv4リスト取得
     *
     * @param string $countryStr
     * @return array
     */
    public function getIpV4List($countryStr)
    {
        if (is_null($this->_allIpStrList)) {
            $this->_allIpStrList = $this->_getIpList();
        }

        $ipStrList = self::_filterIpStrList($this->_allIpStrList, $countryStr);
        $ipStrList = self::_filterIpStrList($ipStrList, "ipv4");

        $ipList = [];
        foreach ($ipStrList as $ipStr) {
            $arr = explode("|", $ipStr);
            $ipList[] = $arr[3]."/".self::_calc_subnet_from_count($arr[4]);
        }

        return $ipList;
    }

    /**
     * IPリスト取得
     *
     * @return array
     */
    private function _getIpList()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->_ipListUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->_addCurlOptionLisdt) {
            curl_setopt_array($ch, $this->_addCurlOptionLisdt);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        $result =  strtolower($result);
        return explode("\n", $result);
    }

    /**
     * フィルタリング
     *
     * @param array $ipStrList
     * @param string countryStr
     * @return array
     */
    private static function _filterIpStrList(array $orgIpStrList, $filteringStr)
    {
        $ipStrList = [];
        foreach ($orgIpStrList as $orgIpStr) {
            if (strpos($orgIpStr, $filteringStr) === false) {
                continue;
            }
            $ipStrList[] = $orgIpStr;
        }
        return $ipStrList;
    }

    /**
     * 設定できるIP数からサブネットマスクを算出
     *
     * @param int $count
     * @param int
     */
    private static function _calc_subnet_from_count($count)
    {
        $bit_count = 0;

        $subnet = self::SUBNET_BASE & ~($count - 1);
        while($subnet) {
            $result = $subnet & 1;
            if ($result) $bit_count += 1;
            $subnet = $subnet >> 1;
        }

        return $bit_count;
    }

}
