<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package log4php
 */

/**
 * Appender for writing to Redis.
 * 
 * This class was originally contributed by Erik Webb.
 * 
 * @link http://github.com/erikwebb/log4php-redis Erik Webb's original submission.
 * @link http://redis.io/ Redis website.
 * 
 * @version $Revision$
 * @package log4php
 * @subpackage appenders
 * @since 2.1
 */
class LoggerAppenderRedis extends LoggerAppender {
	
	// ******************************************
	// ** Constants                            **
	// ******************************************
	
	/** Default value for {@link $host} */
	const DEFAULT_REDIS_HOST = 'localhost';
	
	/** Default value for {@link $port} */
	const DEFAULT_REDIS_PORT = 6379;
	
	/** Default value for {@link $password} */
	const DEFAULT_REDIS_PASSWORD = '';
	
	/** Default value for {@link $listName} */
	const DEFAULT_LIST_NAME = 'log4php';
	
	/** Default value for {@link $prefixName} */
	const DEFAULT_PREFIX_NAME = 'log4php_';

	/** Default value for {@link $timeout} */
	const DEFAULT_TIMEOUT_VALUE = 3000;
	
	// ******************************************
	// ** Configurable parameters              **
	// ******************************************
	
	/** Server on which the redis instance is located. */
	protected $host;
	
	/** Port on which the instance is bound. */
	protected $port;
	
	/** Optional password to use when establishing a connection. */
	protected $password;
	
	/** Name of the key prefix with which to log. */
	protected $prefixName;
			
	/** Timeout value used when connecting to the server (in milliseconds). */
	protected $timeout;
	
	// ******************************************
	// ** Member variables                     **
	// ******************************************

	/** 
	 * Connection to the Redis instance.
	 * @var Redis
	 */
	protected $connection;
		
	public function __construct($name = '') {
		parent::__construct($name);
		$this->host = self::DEFAULT_REDIS_HOST;
		$this->port = self::DEFAULT_REDIS_PORT;
		$this->prefixName = self::DEFAULT_PREFIX_NAME;
		$this->timeout = self::DEFAULT_TIMEOUT_VALUE;
	}
	
	/**
	 * Setup connection.
	 * Based on defined options, this method connects to redis, sets a valid 
	 * key prefix, and optionally authenticates with a password.
	 *  
	 * @throws Exception if the attempt to connect to the requested redis server fails.
	 */
	public function activateOptions() {
		try {
			$this->connection = new Redis();
      // TODO Support pconnect?
      $this->connection->connect($this->host, $this->port, $this->timeout);
      if ($this->password != '') {
        $this->connection->auth($this->password);
      }
      $this->connection->setOption(Redis::OPT_PREFIX, $this->prefixName);
		} catch (RedisException $ex) {
			$this->canAppend = false;
			throw new LoggerException($ex);
		} 

		$this->canAppend = true;
	} 
	
	/**
	 * Appends a new event to the redis store.
	 * 
	 * @throws LoggerException If the pattern conversion or the SET statement fails.
	 */
	public function append(LoggerLoggingEvent $event) {
    if($this->layout !== null) {
			$this->connection->rPush($this->listName, $this->layout->format($event));
		}
	}

	/**
	 * Converts an Exception into an array which can be logged to mongodb.
	 * 
	 * Supports innner exceptions (PHP >= 5.3)
	 * 
	 * @param Exception $ex
	 * @return array
	 */
	protected function formatThrowable(Exception $ex) {
		$array = array(				
			'message'    => $ex->getMessage(),
			'code'       => $ex->getCode(),
			'stackTrace' => $ex->getTraceAsString(),
		);
                        
		if (method_exists($ex, 'getPrevious') && $ex->getPrevious() !== null) {
			$array['innerException'] = $this->formatThrowable($ex->getPrevious());
		}
			
		return $array;
	}
		
	/**
	 * Closes the connection to the logging database
	 */
	public function close() {
		if($this->closed != true) {
			$this->connection->close();
			$this->closed = true;
		}
	}
	
	/** Sets the value of {@link $host} parameter. */
	public function setHost($host) {
		$this->host = $host;
	}
		
	/** Returns the value of {@link $host} parameter. */
	public function getHost() {
		return $this->host;
	}
		
	/** Sets the value of {@link $port} parameter. */
	public function setPort($port) {
		$this->setPositiveInteger('port', $port);
	}
		
	/** Returns the value of {@link $port} parameter. */
	public function getPort() {
		return $this->port;
	}

	/** Sets the value of {@link $password} parameter. */
	public function setPassword($password) {
		$this->setString('password', $password);
	}

	/** Returns the value of {@link $password} parameter. */
	public function getPassword() {
		return $this->password;
	}
	
	/** Sets the value of {@link $listName} parameter. */
	public function setListName($listName) {
		$this->setString('listName', $listName);
	}
		
	/** Returns the value of {@link $listName} parameter. */
	public function getlistName() {
		return $this->listName;
	}
	
	/** Sets the value of {@link $prefixName} parameter. */
	public function setprefixName($prefixName) {
		$this->setString('prefixName', $prefixName);
	}
		
	/** Returns the value of {@link $prefixName} parameter. */
	public function getprefixName() {
		return $this->prefixName;
	}

	/** Sets the value of {@link $timeout} parameter. */
	public function setTimeout($timeout) {
		$this->setPositiveInteger('timeout', $timeout);
	}

	/** Returns the value of {@link $timeout} parameter. */
	public function getTimeout() {
		return $this->timeout;
	}

	/** 
	 * Returns the redis connection.
	 * @return Redis
	 */
	public function getConnection() {
		return $this->connection;
	}
}

