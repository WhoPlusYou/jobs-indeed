<?php namespace JobApis\Jobs\Client\Providers\Test;

use JobApis\Jobs\Client\Collection;
use JobApis\Jobs\Client\Job;
use JobApis\Jobs\Client\Providers\Indeed;

use Mockery as m;

class IndeedTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->params = [
            'publisher' => '12345667',
            'v' => 2,
            'highlight' => 0,
        ];

        $this->client = new Indeed($this->params);
    }

    private function getResultItems($count = 1)
    {
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            array_push($results, [
                'jobtitle' => uniqid(),
                'company' => uniqid(),
                'formattedLocation' => uniqid(),
                'source' => uniqid(),
                'date' => '2015-07-'.rand(1, 31),
                'snippet' => uniqid(),
                'url' => uniqid(),
                'jobkey' => uniqid(),
            ]);
        }

        return $results;
    }

    public function testDefaultUrlAfterConfig()
    {
        $url = $this->client->getUrl();

        $this->assertContains('publisher='.$this->params['publisher'], $url);
        $this->assertContains('v='.$this->params['v'], $url);
        $this->assertContains('highlight='.$this->params['highlight'], $url);
    }

    public function testItWillUseJsonFormatWhenFormatNotProvided()
    {
        $format = $this->client->getFormat();

        $this->assertEquals('json', $format);
    }

    public function testItWillUseJsonFormatWhenInvalidFormatProvided()
    {
        $formatAttempt = uniqid();

        $format = $this->client->setFormat($formatAttempt)->getFormat();

        $this->assertEquals('json', $format);
    }

    public function testItWillUseGetHttpVerb()
    {
        $verb = $this->client->getVerb();

        $this->assertEquals('GET', $verb);
    }

    public function testListingPath()
    {
        $path = $this->client->getListingsPath();

        $this->assertEquals('results', $path);
    }

    public function testUrlContainsSearchParametersWhenProvided()
    {
        $newClient = new Indeed($this->params);

        $url = $newClient->getUrl();

        array_walk($this->params, function ($v, $k) use ($url) {
            $this->assertContains($k.'='.$v, $url);
        });
    }

    public function testUrlContainsSearchParametersWhenSet()
    {
        $params = $this->params;
        $skipParams = ['filter', 'latlong'];

        array_walk($params, function ($v, $k) use ($skipParams) {
            if (!in_array($v, $skipParams)) {
                $value = uniqid();
                $url = $this->client->{'set'.ucfirst($k)}($value)->getUrl();

                $this->assertContains($k.'='.$value, $url);
            }
        });
    }

    public function testItCannotRetriveLocationWhenLocationNotProvided()
    {
        $url = $this->client->getUrl();
        $this->assertNotContains('l=', $url);
    }

    public function testUrlContainsFilterEqualToOneWhenTruthyOptionsProvided()
    {
        $options = [1, -2, 'foo', 2.3e5, true, array(2), "false"];

        array_map(function ($option) {
            $url = $this->client->setFilterDuplicates($option)->getUrl();

            $this->assertContains('filter=1', $url);
        }, $options);
    }

    public function testUrlContainsFilterEqualTo0WhenFalseyOptionsProvided()
    {
        $options = [0, '', false, array(), null];

        array_map(function ($option) {
            $url = $this->client->setFilterDuplicates($option)->getUrl();

            $this->assertNotContains('filter', $url);
        }, $options);
    }

    public function testUrlContainsLatLongEqualToOneWhenTruthyOptionsProvided()
    {
        $options = [1, -2, 'foo', 2.3e5, true, array(2), "false"];

        array_map(function ($option) {
            $url = $this->client->setIncludeLatLong($option)->getUrl();

            $this->assertContains('latlong=1', $url);
        }, $options);
    }

    public function testUrlContainsLatLongEqualTo0WhenFalseyOptionsProvided()
    {
        $options = [0, '', false, array(), null];

        array_map(function ($option) {
            $url = $this->client->setIncludeLatLong($option)->getUrl();

            $this->assertNotContains('latlong', $url);
        }, $options);
    }

    public function testItWillIncludeUserIpIfAvailableAndNotProvided()
    {
        $ip = uniqid();
        $_SERVER['REMOTE_ADDR'] = $ip;
        $client = new Indeed;

        $url = $client->getUrl();

        $this->assertContains('userip='.$ip, $url);
    }

    public function testItWillIncludeUserAgentIfAvailableAndNotProvided()
    {
        $agent = uniqid();
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $client = new Indeed;

        $url = $client->getUrl();

        $this->assertContains('useragent='.$agent, $url);
    }

    public function testItCanSetAllMethodsInReadme()
    {
        $attributes = [
            'keyword' => uniqid(),
            'location' => uniqid(),
            'sort' => uniqid(),
            'radius' => uniqid(),
            'siteType' => uniqid(),
            'jobType' => uniqid(),
            'start' => rand(1,5),
            'limit' => rand(1,5),
            'daysBack' => uniqid(),
            'filterDuplicates' => rand(0,1),
            'includeLatLong' => rand(0,1),
            'country' => uniqid(),
            'chnl' => uniqid(),
            'userIp' => uniqid(),
            'userAgent' => uniqid(),
        ];
        $client = new Indeed;
        // Set all values
        foreach ($attributes as $key => $val) {
            $client->{'set'.ucfirst($key)}($val);
        }
        // Get all values
        foreach ($attributes as $key => $val) {
            $this->assertEquals($val, $client->{'get'.ucfirst($key)}(), "$key was not set or retrieved properly.");
        }
    }

    /**
     * @expectedException JobApis\Jobs\Client\Exceptions\MissingParameterException
     */
    public function testItWillThrowExceptionWhenPublisherNotProvided()
    {
        $client = new Indeed;

        $results = $client->setKeyword(uniqid())->getJobs();
    }

    public function testItCanCreateJobFromPayload()
    {
        $payload = $this->createJobArray();
        $results = $this->client->createJobObject($payload);

        $this->assertEquals($payload['jobtitle'], $results->getTitle());
        $this->assertEquals($payload['jobtitle'], $results->getName());
        $this->assertEquals($payload['snippet'], $results->getDescription());
        $this->assertEquals($payload['company'], $results->getCompanyName());
        $this->assertEquals($payload['url'], $results->getUrl());
        $this->assertEquals($payload['jobkey'], $results->getSourceId());
        $this->assertEquals($payload['formattedLocation'], $results->getLocation());
        $this->assertEquals($payload['latitude'], $results->getLatitude());
        $this->assertEquals($payload['longitude'], $results->getLongitude());

        $locationArray = explode(' ', $payload['formattedLocationFull']);
        $this->assertEquals($locationArray[2], $results->getPostalCode());
    }

    public function testItCanConnect()
    {
        $provider = $this->getProviderAttributes();

        for ($i = 0; $i < $provider['jobs_count']; $i++) {
            $payload['results'][] = $this->createJobArray();
        }

        $responseBody = json_encode($payload);

        $job = m::mock(Job::class);
        $job->shouldReceive('setQuery')->with($provider['keyword'])
            ->times($provider['jobs_count'])->andReturnSelf();
        $job->shouldReceive('setSource')->with($provider['source'])
            ->times($provider['jobs_count'])->andReturnSelf();

        $response = m::mock('GuzzleHttp\Message\Response');
        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $http = m::mock('GuzzleHttp\Client');
        $http->shouldReceive(strtolower($this->client->getVerb()))
            ->with($this->client->getUrl(), $this->client->getHttpClientOptions())
            ->once()
            ->andReturn($response);
        $this->client->setClient($http);

        $results = $this->client->getJobs();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount($provider['jobs_count'], $results);
    }

    public function testItCanRetreiveRealResults()
    {
        if (!getenv('PUBLISHER')) {
            $this->markTestSkipped('PUBLISHER not set. Real API call will not be made.');
        }

        $client = new Indeed([
            'publisher' => getenv('PUBLISHER')
        ]);

        $keyword = 'engineering';
        $client->setKeyword($keyword)->setIncludeLatLong(1);
        $results = $client->getJobs();

        $this->assertInstanceOf('JobApis\Jobs\Client\Collection', $results);

        foreach($results as $job) {
            $this->assertEquals($keyword, $job->query);
        }
    }

    private function createJobArray()
    {
        $formattedLocation = uniqid().', '.uniqid();
        return [
            'jobtitle' => uniqid(),
            'company' => uniqid(),
            'formattedLocation' => $formattedLocation,
            'formattedLocationFull' => $formattedLocation.' '.uniqid(),
            'snippet' => uniqid(),
            'date' => '2015-07-'.rand(1, 31),
            'url' => uniqid(),
            'jobkey' => uniqid(),
            'latitude' => uniqid(),
            'longitude' => uniqid(),
        ];
    }

    private function getProviderAttributes($attributes = [])
    {
        $defaults = [
            'path' => uniqid(),
            'format' => 'json',
            'keyword' => uniqid(),
            'source' => uniqid(),
            'params' => [uniqid()],
            'jobs_count' => rand(2, 10),

        ];

        return array_replace($defaults, $attributes);
    }
}
