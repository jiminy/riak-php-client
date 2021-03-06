<?php
/**
 * Riak PHP Client
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Apache License, Version 2.0 that is
 * bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to <eng@basho.com> so we can send you a copy immediately.
 *
 * @category   Basho
 * @copyright  Copyright (c) 2013 Basho Technologies, Inc. and contributors.
 */
namespace Basho\Riak;

use Basho\Riak\MapReduce;

/**
 * Riak
 *
 * @category   Basho
 * @author     Riak team (https://github.com/basho/riak-php-client/contributors)
 */
class Riak
{
    /**
     * Construct a new Client object.
     *
     * @param string $host - Hostname or IP address (default '127.0.0.1')
     * @param int $port - Port number (default 8098)
     * @param string $prefix - Interface prefix (default "riak")
     * @param string $mapred_prefix - MapReduce prefix (default "mapred")
     */
    public function __construct($host = '127.0.0.1', $port = 8098, $prefix = 'riak', $mapred_prefix = 'mapred')
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->mapred_prefix = $mapred_prefix;
        $this->indexPrefix = 'buckets';
        $this->clientid = 'php_' . base_convert(mt_rand(), 10, 36);
        $this->r = 2;
        $this->w = 2;
        $this->dw = 2;
        $this->schema = 'http';
        $this->cert = '';
        $this->key = '';
        $this->password = '';
        $this->userpwd = null;
        $this->transport = new Transport($this);
    }

    /**
     * Get the R-value setting for this Client
     *
     * Default: 2
     *
     * @return integer
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * Set the R-value for this Client
     *
     * This value will be used
     * for any calls to get(...) or getBinary(...) where 1) no
     * R-value is specified in the method call and 2) no R-value has
     * been set in the Bucket.
     *
     * @param integer $r - The R value.
     * @return $this
     */
    public function setR($r)
    {
        $this->r = $r;

        return $this;
    }

    /**
     * Get the W-value setting for this Client
     *
     * Default: 2
     *
     * @return integer
     */
    public function getW()
    {
        return $this->w;
    }

    /**
     * Set the W-value for this Client
     *
     * See setR(...) for a description of how these values are used.
     *
     * @param integer $w - The W value.
     * @return $this
     */
    public function setW($w)
    {
        $this->w = $w;

        return $this;
    }

    /**
     * Get the DW-value for this ClientOBject
     *
     * Default: 2
     *
     * @return integer
     */
    public function getDW()
    {
        return $this->dw;
    }

    /**
     * Set the DW-value for this Client
     *
     * See setR(...) for a description of how these values are used.
     *
     * @param  integer $dw - The DW value.
     * @return $this
     */
    public function setDW($dw)
    {
        $this->dw = $dw;

        return $this;
    }

    /**
     * Get the clientID for this Client.
     *
     * @return string
     */
    public function getClientID()
    {
        return $this->clientid;
    }

    /**
     * Set the clientID for this Client
     *
     * Should not be called unless you know what you are doing.
     *
     * @param string $clientId - The new clientID.
     * @return $this
     */
    public function setClientID($clientId)
    {
        $this->clientid = $clientId;

        return $this;
    }

    /**
     * Get the bucket by the specified name
     *
     * Since buckets always exist, this will always return a Bucket.
     *
     * @param $name
     * @return Bucket
     */
    public function bucket($name)
    {
        return new Bucket($this, $name);
    }

    /**
     * Get all buckets
     *
     * @return array() of Bucket objects
     */
    public function buckets()
    {
        $url = $this->transport->buildRestPath();
        $response = $this->transport->httpRequest('GET', $url . '?buckets=true');
        $response_obj = json_decode($response[1]);
        $buckets = array();
        foreach ($response_obj->buckets as $name) {
            $buckets[] = $this->bucket($name);
        }

        return $buckets;
    }

    /**
     * Check if the Riak server for this Client is alive
     *
     * @return boolean
     */
    public function isAlive()
    {
        $url = $this->schema . '://' . $this->host . ':' . $this->port . '/ping';
        $response = $this->transport->httpRequest('GET', $url);

        return ($response != null) && ($response[1] == 'OK');
    }


    # MAP/REDUCE/LINK FUNCTIONS

    /**
     * Start assembling a Map/Reduce operation
     *
     * @see MapReduce::add()
     * @param $params
     * @return MapReduce
     */
    public function add($params)
    {
        $mr = new MapReduce($this);
        $args = func_get_args();

        return call_user_func_array(array(&$mr, "add"), $args);
    }

    /**
     * Start assembling a Map/Reduce operation
     *
     * This command will return an error unless
     * executed against a Riak Search cluster.
     *
     * @see MapReduce::search()
     * @param $params
     * @return MapReduce
     */
    public function search($params)
    {
        $mr = new MapReduce($this);
        $args = func_get_args();

        return call_user_func_array(array(&$mr, "search"), $args);
    }

    /**
     * Start assembling a Map/Reduce operation.
     *
     * @see MapReduce::link()
     * @param $params
     * @return mixed
     */
    public function link($params)
    {
        $mr = new MapReduce($this);
        $args = func_get_args();

        return call_user_func_array(array(&$mr, "link"), $args);
    }

    /**
     * Start assembling a Map/Reduce operation.
     *
     * @see MapReduce::map()
     * @param $params
     * @return mixed
     */
    public function map($params)
    {
        $mr = new MapReduce($this);
        $args = func_get_args();

        return call_user_func_array(array(&$mr, "map"), $args);
    }

    /**
     * Start assembling a Map/Reduce operation.
     *
     * @see MapReduce::reduce()
     * @param $params
     * @return mixed
     */
    public function reduce($params)
    {
        $mr = new MapReduce($this);
        $args = func_get_args();

        return call_user_func_array(array(&$mr, "reduce"), $args);
    }
}
