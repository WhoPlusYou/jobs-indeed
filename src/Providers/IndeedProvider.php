<?php namespace JobApis\Jobs\Client\Providers;

use JobApis\Jobs\Client\Job;

class IndeedProvider extends AbstractProvider
{
    /**
     * The meta data that came from the request
     * Is set AFTER the getJobs() has been called
     * 
     * @var array
     */
    protected $meta = [];

    /**
     * Returns the standardized job object
     *
     * @param array $payload
     *
     * @return \JobApis\Jobs\Client\Job
     */
    public function createJobObject($payload)
    {
        $job = new Job([
            'title' => $payload['jobtitle'],
            'name' => $payload['jobtitle'],
            'description' => $payload['snippet'],
            'url' => $payload['url'],
            'sourceId' => $payload['jobkey'],
            'location' => $payload['formattedLocation'],
            'javascriptFunction' => $payload['onmousedown'],
        ]);

        $job = $this->setJobLocation($job, $payload['formattedLocation']);

        $postalCode = str_replace($payload['formattedLocation'].' ', '', $payload['formattedLocationFull']);

        return $job->setCompany($payload['company'])
            ->setDatePostedAsString($payload['date'])
            ->setPostalCode($postalCode)
            ->setLatitude($payload['latitude'])
            ->setLongitude($payload['longitude']);
    }

    /**
     * Job response object default keys that should be set
     *
     * @return  string
     */
    public function getDefaultResponseFields()
    {
        return [
            'jobtitle',
            'company',
            'formattedLocation',
            'formattedLocationFull',
            'source',
            'date',
            'snippet',
            'url',
            'jobkey',
            'latitude',
            'longitude'
        ];
    }

    /**
     * Get listings path
     *
     * @return  string
     */
    public function getListingsPath()
    {
        return 'results';
    }

    /**
     * Attempt to parse and add location to Job
     *
     * @param Job     $job
     * @param string  $location
     *
     * @return  Job
     */
    private function setJobLocation(Job $job, $location)
    {
        $location = static::parseLocation($location);

        if (isset($location[0])) {
            $job->setCity($location[0]);
        }
        if (isset($location[1])) {
            $job->setState($location[1]);
        }

        return $job;
    }

    /**
     * Makes the api call and returns a collection of job objects
     *
     * @return  \JobApis\Jobs\Client\Collection
     * @throws MissingParameterException
     */
    public function getJobs()
    {
        // Verify that all required query vars are set
        if ($this->query->isValid()) {
            // Get the response from the client using the query
            $response = $this->getClientResponse();
            // Get the response body as a string
            $body = (string) $response->getBody();
            // Parse the string
            $payload = $this->parseAsFormat($body, $this->getFormat());
            // Gets listings if they're nested
            $listings = is_array($payload) ? $this->getRawListings($payload) : [];
            $this->setMetaData($payload);
            // Return a job collection
            return $this->getJobsCollectionFromListings($listings);
        } else {
            throw new MissingParameterException("All Required parameters for this provider must be set");
        }
    }

    /**
     * Set the meta data that came from the request
     * 
     * @param void
     */
    public function setMetaData($payload)
    {
        unset($payload['results']);

        $this->meta = $payload;
    }

    /**
     * Get the meta data that came from the request
     * 
     * @return array
     */
    public function getMetaData()
    {
        return $this->meta;
    }
}
