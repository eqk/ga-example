<?php

class GA
{
    /**
     * @var \Google_Client
     */
    public $client;

    /**
     * @var \Google_Service_Analytics
     */
    public $analytics;

    public function __construct($credentialsJsonPath)
    {

        $this->client = $this->getGoogleClient($credentialsJsonPath);
        $this->analytics = new \Google_Service_Analytics($this->client);
    }

    //Клиент с сервайс акк
    public static function getGoogleClient($credentialsPath)
    {
        $client = new \Google_Client();
        $client->setAuthConfig($credentialsPath);
        $client->setApplicationName('MobExp analytics');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        $client->setRedirectUri(route('google-auth'));
        return $client;
    }

    //Первый акк в котором меньше 50 ресурсов
    public function getFirstFreeAccountId()
    {
        $accounts = $this->analytics->management_accounts->listManagementAccounts();

        if ($accounts->count() < 1) {
            throw new No_Free_Accounts_Exception();
        }
        foreach ($accounts as $account) {
            $properties_count = $this->analytics->management_webproperties
                ->listManagementWebproperties($account->getId())->count();
            if ($properties_count < 50) {
                return $account->getId();
            }
        }
        throw new No_Free_Accounts_Exception();
    }

    //Новый ресурс в указанный или первый свободный акк
    public function createNewProperty($name, $url, $accountId = null)
    {
        if (is_null($accountId)) {
            $accountId = $this->getFirstFreeAccountId();
        }
        $property = new \Google_Service_Analytics_Webproperty();
        $property->setIndustryVertical('check');
        $property->setName($name);
        $property->setWebsiteUrl($url);
        $property->setSelfLink($url);
        $property = $this->analytics->management_webproperties->insert($accountId, $property);
        return $property;
    }

    //Получает id первого представления по id аккаунта и ресурса
    public function getFirstViewInProperty($accountId, $propertyId)
    {
        $profiles = $this->analytics->management_profiles->listManagementProfiles($accountId, $propertyId);
        if ($profiles->count() == 0) {
            throw new No_Free_Accounts_Exception();
        }
        return $profiles->getItems()[0]->getId();
    }

    //Метод для запросов
    public function getGaData($viewId, \DateTime $startDate, \DateTime $endDate, $metricsName, $disName)
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate->format('Y-m-d'));
        $dateRange->setEndDate($endDate->format('Y-m-d'));

        $metrics = new \Google_Service_AnalyticsReporting_Metric();
        $metrics->setExpression('ga:' . $metricsName);
        $metrics->setAlias($metricsName);

        $dimensions = new \Google_Service_AnalyticsReporting_Dimension();
        $dimensions->setName('ga:' . $disName);


        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setDimensions(array($dimensions));
        $request->setMetrics(array($metrics));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        $reporter = new \Google_Service_AnalyticsReporting($this->client);
        return $reporter->reports->batchGet($body);
    }

    //Создание цели
    public function createNewGoal($accountId, $webPropertyId, $profileId)
    {
        $goal = new \Google_Service_Analytics_Goal();
        $goal->setActive(true);
        //TODO

        $this->analytics->management_goals->insert($accountId, $webPropertyId, $profileId,
            $goal);
    }

    public function getBounceRate($profileId, \DateTime $start, \DateTime $end)
    {
        return $this->getGaData($profileId, $start, $end, 'bounceRate', 'day');
    }

    public function getAvgSessionDuration($profileId, \DateTime $start, \DateTime $end)
    {
        return $this->getGaData($profileId, $start, $end, 'avgSessionDuration', 'day');
    }

    public function getPageDepth($profileId, \DateTime $start, \DateTime $end, $metricsName)
    {
        return $this->getGaData($profileId, $start, $end, $metricsName, 'pageDepth');
    }

    public function getGoalCompletions($profileId, \DateTime $start, \DateTime $end)
    {
        return $this->getGaData($profileId, $start, $end, 'goalCompletionsAll', 'day');
    }

    public function getTrafficSource($profileId, \DateTime $start, \DateTime $end, $metricsName)
    {
        return $this->getGaData($profileId, $start, $end, $metricsName, 'source');
    }


}

class Access_Token_Exception extends \Exception
{
}

class No_Free_Accounts_Exception extends \Exception
{
}

class No_Profiles_Exception extends \Exception
{
}