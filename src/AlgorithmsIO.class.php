<?php
/*
 *	(c)2012 Algorithms.IO, Inc.
 *	Created By: MRR
 *	Created On: 2012-06-01
 *	For more information and usage terms see: https://www.algorithms.io/api/php (TODO)
 */
namespace AlgorithmsIO {
	require_once("HTTP/Request2.php"); // http://pear.php.net/package/HTTP_Request2/

        class Config {
            
            protected static $_instance;
            protected static $_config;
            
            protected function __construct() # we shouldn't be constructed directly, should instead call getInstance
            { }

            protected function __clone() # No direct clones
            { }

            public static function getInstance() 
            {
                if( self::$_instance === NULL ) {
                    self::$_instance = new self();
                    self::$_config = array();
                    self::set(); // Setup the defaults
                }
                return self::$_instance;
            }

            public function set($config=null) {
                if($config == null) { $config = self::defaults(); }
                self::$_config = array_replace_recursive(self::$_config, $config);
                self::$_config = $config;
            }
            
            public function get($key=null) {
                if($key==null) {
                    return self::$_config;
                }
                return self::$_config[$key];
            }
            
            public function defaults() {
                	$defaults = array(
                            "Authentication" => array(				"url_login"		=>"https://www.algorithms.io/login/process?f=/dashboard",
				"url_authToken"		=>"https://www.algorithms.io/dashboard",
				"url_credits"		=>"https://v1.api.algorithms.io/credits",
				//"authToken"		=>"541b393f52b097d3e589ea63ccdfd49e", // Default MRR Test Account
				"expiration"		=>null, // Not implemented
				"username"		=>"mark@mark.org",
				"password"		=>"test",
				"debug"			=>false, 
                            ),
                            "DataSource"   => array(
				"url_list"		=>"https://v1.api.algorithms.io/dataset",
				"url_upload"		=>"https://v1.api.algorithms.io/dataset",
				"url_delete"		=>"http://v1.api.algorithms.io/dataset/id/",
				"type"			=>"rec", // what is the type?
				"name"			=>"no_name", // This is friendly_name
				"description"		=>"no_description",
				"version"		=>1,
				"filepath"		=>"/mnt/md0/sample_datasets/", // filename can contain path and we will parse it out
				"filename"		=>"Movie_Lens_100k_data.csv",
				"id"			=>null,
				"authobj"		=>null,
				"debug"			=>false,
                            ),
                            "Algorithm"     => array(
				"url_algorithm_list"	=>"https://v1.api.algorithms.io/algorithms",
				"authobj"		=>null,
				"debug"			=>false,
                            ),
                            "Job"           => array(
				"url_runJob"		=>"https://v1.api.algorithms.io/jobs",
				"authobj"		=>null,
				"debug"			=>false,
				"finished"		=>false,
                            ),     
                            "Mapper"        => array(
				"mappings"		=>array(),
				"debug"			=>false,
                            ),
                        );
                        return $defaults;
            }
            
            public static function checkExists() 
            {
                return self::$_instance;
            }

        }
        
/******************************************** Authentication ***********************************************/
	class Authentication extends Base {
                
                public $debug = false;
                
		public function __construct($options = array()) {
                        
                        $this->defaults = Config::getInstance()->get("Authentication");
			$this->_oData = array_replace_recursive($this->defaults, $options);
                        $this->debug("DEBUG201208031243: Authentication _oData=". print_r($options, true));
                        if(isset($this->_oData["debug"])) {
                            $this->debug = $this->_oData["debug"];
                        }
                        if(isset($this->_oData["authToken"])) {
                            $this->authToken = $this->_oData["authToken"];
                        }
                        
			if(!$this->authToken) {
				// Login and grab the token
				$this->user_login();
			}
		}	

		private function user_login() {
			$httprequest = new HTTP_Connection($this->url_login,null,array());
			$httprequest->setMethod(\HTTP_Request2::METHOD_POST);
			$httprequest->setCookieJar(); // Use the cookie jar to keep track of PHP session
			$httprequest->addPostParameter(array(
				'username'	=>$this->username(),
				'password'	=>$this->password(),
				'login'		=>'login',
			));
			
			try {
				if($this->debug) { $httprequest->attach(new HTTP_Request2_Observer_All()); } // Add an observer to look at the transaction
				$response = $httprequest->send();
				$cookieJar = $httprequest->getCookieJar();
				$this->debug("DEBUG201206011529: CookieJar=".print_r($cookieJar, true));

				if($response->getStatus() == 200 || $response->getStatus() == 302) {
					// We either got a response, or we got a redirect... we'll take it as success
				} else {
					$error = sprintf("ERROR201206021030: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_login, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021031: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_login, $response->getStatus());
					$this->error($error);
					return false;

			}	
			// Get the authToken
			$httprequest = new HTTP_Connection($this->url_authToken,null,array());
			//$httprequest->setMethod(\HTTP_Request2::METHOD_POST);
			$this->debug("DEBUG201206021117: cookieJar=".print_r($cookieJar, true));
			$httprequest->setCookieJar($cookieJar); // Use the cookie jar from previous request
			
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200) {
					$body = $response->getBody();	
					preg_match('/Auth Token: (.*)/', $body, $matches);
					if($matches[1] && strlen($matches[1])==32) {
						$this->authToken($matches[1]);
						return $this->authToken();
					} else {
						$error = sprintf("ERROR201206021059: AlgorithmsIO\\Authentication: Could not find authToken from %s: Matched=%s : Length=%s : Body=%s", $this->url_authToken, print_r($matches,true), strlen($matches[1]), $body);
						$this->error($error);
					}
				} else if($response->getStatus() == 203) {
					// Access Denied	
					$this->authToken = null;
				} else {
					$error = sprintf("ERROR201206021032: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_authToken, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021033: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_authToken, $response->getStatus());
					error_log($error);
					return false;
			}	

		}

		public function credits() {
			// I don't cache this, as it could change regularly - MRR20120602
			$http_headers = array(
				"authToken"		=>$this->authToken,
			);	
			$httprequest = new HTTP_Connection($this->url_credits,null,array());
			//$httprequest->setMethod(\HTTP_Request2::METHOD_POST);
			if($this->debug) { $httprequest->attach(new HTTP_Request2_Observer_All()); } // Add an observer to look at the transaction
			$httprequest->setHeader($http_headers);
	
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200 || $response->getStatus() == 201) {
					// Success
					$credits=0;
					$body = $response->getBody();	
					$json_response = json_decode($body);
					$credits = $json_response->credits_available;
					$this->debug("DEBUG201206022053: Authorization->credits: ".print_r($json_response,true));
					return $credits;
				} else {
					$error = sprintf("ERROR201206021606: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_upload, $response->getStatus());
						$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021607: AlgorithmsIO\\Authentication: Response from %s: %s", $this->url_upload, $response->getStatus());
					error_log($error);
					return false;

			}	
			// Get the authToken
			// $this->authToken = $response...
			$httprequest->close();

		}

		public function authenticated() {
			if ($this->authToken) {
				return true;
			} else {
				return false;
			}
		}

		public function expired() {
			/* if ($this->expiration < time) {
			 *	// Our token expired
			 * 	return true;
			 * } else {
			 */
				return false;
			 //}
		}
	}

/******************************************** DataSource ***********************************************/
	class DataSource extends Base {
//public $debug=1;
		public function __construct($options = array()) {
                        $this->defaults = Config::getInstance()->get("DataSource");
                        $this->debug("DEBUG201208031615: DataSource defaults=". print_r($this->defaults, true));
			$this->_oData = array_replace_recursive($this->defaults, $options);
                        $this->debug("DEBUG201208031244: DataSource _oData=". print_r($this->_oData, true));
                        
			if(!$this->authobj && !isset($this->_oData["authobj"])) {
                                $this->warning("WARNING201208031252: An authobj was not passed into DataSource, creating a new one");
				$this->authobj = new Authentication();
			}
		}

		public function filepath($filepath=null) {
			if($filepath) {
				$this->_oData["filepath"] = $filepath;
			}
			return $this->_oData["filepath"];
		}

		public function filename($filename=null) {
			if($filename) {
				$this->_oData["filepath"] .= dirname($filename);
				$this->_oData["filename"] = basename($filename);
			}
			return $this->_oData["filename"];
		}

		public function upload() {
			// DataSource Upload: curl -i  -H "authToken: <authToken>" -H "type:rec" -H "friendly_name:Movie_Lens_100k" -H "friendly_description:100k rows of user prefs" -H "version:1" -F theFile=@Movie_Lens_100k_data.csv https://v1.api.algorithms.io/dataset
			$http_headers = array(
				"authToken"		=>$this->authobj->authToken,
				"type"			=>$this->type,
				"friendly_name"		=>$this->name,
				"friendly_description"	=>$this->description,
				"version"		=>$this->version,
			);
			$postfields = array(
				"theFile"		=>"@".$this->filepath."/".$this->filename,
			);

			$this->debug("DEBUG201206011142: Dump of http_headers ". print_r($http_headers, true));
			$this->debug("DEBUG201206011315: filepath: ". print_r($this->filepath, true));
			$httprequest = new HTTP_Connection($this->url_upload,null,array());
			$httprequest->setMethod(\HTTP_Request2::METHOD_POST);
			if($this->debug) { $httprequest->attach(new HTTP_Request2_Observer_All()); } // Add an observer to look at the transaction
			$httprequest->setHeader($http_headers);
			
			$httprequest->addUpload("theFile", $this->filepath."/".$this->filename);

			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200 || $response->getStatus() == 201) { // Either Successful or Created response from server
					// Success
					$body = $response->getBody();	
					$json_response = json_decode($body);
					// Format of REST API changed from this to the if below on 20120602 - MRR20120602
					//if($json_response->data[0]->id_seq) {
					//	$this->id($json_response->data[0]->id_seq);
					if($json_response[0]->data > 0){
						$this->id($json_response[0]->data);
					} else {
						$this->error(sprintf("ERROR20120601358: AlgorithmsIO\\DataSource->upload: Returned JSON format is not correct: %s \n---BODY=",print_r($json_response, true), $body));
					}
					return $this->id();
				} else {
					$error = sprintf("ERROR201206011329: AlgorithmsIO\\DataSource->upload: Response from %s: %s", $this->url_upload, $response->getStatus());
						$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206011333: AlgorithmsIO\\DataSource->upload: Response from %s: %s", $this->url_upload, $response->getStatus());
					error_log($error);
					return false;

			}	
			$httprequest->close();
		}

		public function listAll() {
			if(!$this->authobj()) { $this->error("ERROR201206061527: AlgorithmsIO\\DataSource must have an Authorization Object"); }
			if($this->datasourceList()) {
				// We have a list already, return that
				return $this->datasourceList();
			}
			$http_headers = array(
				"authToken"		=>$this->authobj->authToken,
			);
			$httprequest = new HTTP_Connection($this->url_list,null,array());
			$httprequest->setHeader($http_headers);
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200) { // Success or No Content
					// Success
					$body = $response->getBody();	
                                        $this->debug("DEBUG201208031425: Response Body: ".$body);
					$json_response = json_decode($body);
					if($json_response->data) {
						$this->debug(sprintf("DEBUG201206061822: DataSource JSON->all: %s", print_r($json_response->data, true)));
						return $json_response->data;
					} else {
						$this->error(sprintf("ERROR201206061820: AlgorithmsIO\\DataSource: JSON is not in expected format: %s",print_r($json_response, true)));
						return false;
					}
				} else {
					$error = sprintf("ERROR201206061830: AlgorithmsIO\\DataSource: Response from %s: %s", $this->url_list, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021831: AlgorithmsIO\\DataSource: Response from %s: %s", $this->url_list, $response->getStatus());
					$this->error($error);
					return false;

			}	
				
		}
		public function delete() {
			//curl -X DELETE -H "authToken: <Authentication Token>" http://v1.api.algorithms.io/dataset/id/<datasource_id_seq> -v
			if(!$this->id()) {
				$this->error("ERROR201206011408: AlgorithmsIO\\DataSource->delete must have a valid DataSource ID");
				return false;
			}
			if(!$this->authobj) { $this->error("ERROR201206021813: AlgorithmsIO\DataSource must have a valide Authorization Object"); }
			$http_headers = array(
				"authToken"		=>$this->authobj->authToken,
			);
			$httprequest = new HTTP_Connection($this->url_delete.$this->id,null,array());
			$httprequest->setHeader($http_headers);
			$httprequest->setMethod("DELETE");
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200 || $response->getStatus() == 204) { // Success or No Content
					// Success
					$body = $response->getBody();	
					$json_response = json_decode($body);
					$this->debug("DEBUG201206011411: Delete Response: ".print_r($json_response,true));
					return true;
				} else {
					$error = sprintf("ERROR201206011109: AlgorithmsIO\\DataSource->delete(): Response from %s: %s", $this->url_delete, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206011110: AlgorithmsIO\\DataSource->delete(): Response from %s: %s", $this->url_delete, $response->getStatus());
					$this->error($error);
					return false;

			}	
	
		}


	}

/******************************************** Algorithms ***********************************************/
	class Algorithm extends Base{
                public $debug = false;
		public function __construct($options = array()) {
                        $this->defaults = Config::getInstance()->get("Algorithm");
			$this->_oData = array_replace_recursive($this->defaults, $options);
		}

		public function listAll() {
			if(!$this->authobj()) { $this->error("ERROR201206021810: AlgorithmsIO\\Algorithms must have an Authorization Object"); }
			if($this->algoList()) {
				// We have a list already, return that
				return $this->algoList();
			}
			$http_headers = array(
				"authToken"		=>$this->authobj->authToken,
			);
                        $this->debug("DEBUG201207291355: Connecting to: ".$this->url_algorithm_list);
			$httprequest = new HTTP_Connection($this->url_algorithm_list,null,array());
                        //$this->debug = true;
                        if($this->debug) { $httprequest->attach(new HTTP_Request2_Observer_All()); } // Add an observer to look at the transaction
			$httprequest->setHeader($http_headers);
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200) { // Success or No Content
					// Success
					$body = $response->getBody();	
					$json_response = json_decode($body);
					if($json_response->all) {
						$this->debug(sprintf("DEBUG201206021822: Algorithms JSON->all: %s", print_r($json_response->all, true)));
						return $json_response->all;
					} else {
						$this->error(sprintf("ERROR201206021820: AlgorithmsIO\\Algorithms: JSON is not in expected format: %s",print_r($json_response, true)));
						return false;
					}
				} else {
					$error = sprintf("ERROR201206021830: AlgorithmsIO\\Algoirthms: Response from %s: %s", $this->url_algorithm_list, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021831: AlgorithmsIO\\Algorithms: Response from %s: %s", $this->url_algorithm_list, $response->getStatus());
					$this->error($error);
					return false;

			}	
				
		}

	

	}

/********************************************    Job     ***********************************************/
	class Job extends Base {
		public function __construct($options = array()) {
                        $this->defaults = Config::getInstance()->get("Job");
			$this->_oData = array_replace_recursive($this->defaults, $options);
		}

		public function run($input_variables = array()) {
			if(!$this->authobj()) { $this->error("ERROR201206021810: AlgorithmsIO\\Job->run() must have an Authorization Object"); }
			if(!$this->mapper()) { $this->error("ERROR201206021911: AlgorithmsIO\\Job->run() must have a Mapper"); return false;}
			$mapper = $this->mapper;
			if(!$mapper->algorithm()->id()) { $this->error("ERROR201206021907: AlgorithmsIO\\Job->run() must have an Algorithm ID"); }
			if(!$mapper->datasource()) { $this->error("ERROR201206021907: AlgorithmsIO\\Job->run() must have an Algorithm ID"); }
			$http_headers = array(
				"authToken"		=>$this->authobj->authToken,
			);

			//TODO: replace this with a \AlgorithmsIO\Job->something() call - MRR20120602
			$input_variables=array_replace_recursive($this->mapper()->mappings(), $input_variables);
			$input_variables=array_replace_recursive($input_variables,array("datasource_id_seq"=>$mapper->datasource()->id()));
			$job=array(
				"job" => array(
					"algorithm"		=>array("id"=>$mapper->algorithm()->id()),
					"input_variables"	=>$input_variables,
				),
			);

			$httprequest = new HTTP_Connection($this->url_runJob,null,array());
			$this->debug(sprintf("DEBUG201206022024: post=%s",json_encode($job)));
			$httprequest->addPostParameter(array(
				'job_params'	=>json_encode($job),
			));
			$httprequest->setHeader($http_headers);
			$httprequest->setMethod(\HTTP_Request2::METHOD_POST);
			if($this->debug) { $httprequest->attach(new HTTP_Request2_Observer_All()); } // Add an observer to look at the transaction
			try {
				$response = $httprequest->send();

				if($response->getStatus() == 200 || $response->getStatus() == 201) { // Success or Created
					// Success
					$body = $response->getBody();	
					$this->debug(sprintf("DEBUG201206021935: Body: %s", print_r($body, true)));
					$json_response = json_decode($body);
					if($json_response[0]->output->data) {
						$job_data = $json_response[0]->output->data;
						$this->data($job_data);
						$this->finished(true); // Done
						$this->debug(sprintf("DEBUG201206021929: Job JSON: %s", print_r($job_data, true)));
						return true;
					} else {
						$this->error(sprintf("ERROR201206021930: AlgorithmsIO\\Job: JSON is not in expected format: %s",print_r($json_response, true)));
						return false;
					}
				} else {
					$error = sprintf("ERROR201206021931: AlgorithmsIO\\Job: Response from %s: %s", $this->url_runJob, $response->getStatus());
					$this->error($error);
					return false;
				}
			} catch (HTTP_Request2_Exception $e) {
					$error = sprintf("ERROR201206021932: AlgorithmsIO\\Algorithms: Response from %s: %s", $this->url_runJob, $response->getStatus());
					$this->error($error);
					return false;
			}	
				
		}


	}

/********************************************   Mapper   ***********************************************/
	class Mapper extends Base {
		// TODO: SHould expand to have a Mapper:add(array())
		public function __construct($options = array()) {
                        $this->defaults = Config::getInstance()->get("Mapper");
			$this->_oData = array_replace_recursive($this->defaults, $options);
		}

		// Note we don't check to see if any already exist, we really just merge them - MRR20120602
		public function add($mappings = array()) {
			if($mappings) {
				$this->debug(sprintf("DEBUG201206021857: Adding mappings: %s",print_r($mappings,true)));
				$this->mappings(array_replace_recursive($this->mappings(), $mappings));
			}
			return $this->mappings();
		}

	}


/********************************************    Base    ***********************************************/
	class Base {
		public $_oData;

		// Algorithms Base class - Defines getters, setters, and error handling
		public function debug($msg) {
			if($this->debug) {
				error_log($msg);
			}
		}

		public function warning($msg) {
			error_log($msg);
			if($this->debug) {
				echo $msg;
			}
		}

		public function error($msg) {
			$msg .= " *** Backtrace: ".print_r(debug_backtrace(null,5),true);
			error_log($msg);
			if($this->debug) {
				echo $msg;
			}
		}

		// Magic getters and setters
		public function __set($property, $value) {
			return $this->_oData[$property] = $value;
		}

		public function __get($property) {
			if(isset($this->_oData[$property])) {
				return $this->_oData[$property];
			} else {
				//if($this->debug) { $this->error("ERROR201206011425: $property is not defined "); }
			}
		}

		// I prefer using methods instead of setting $this vars. This way we can override. MRR20120601
		public function __call($name, $arguments) {
			if (isset($arguments) && !empty($arguments)) {
				$this->_oData[$name]=$arguments[0];
				return $this->_oData[$name];
			} else {
				if (isset($this->_oData[$name])) {
					return $this->_oData[$name];
				} else {
					return null;
				}
			}
		}
	} 

/******************************************** Connection ***********************************************/
	class HTTP_Connection extends \HTTP_Request2 {
    		//ORIGINAL: const REGEXP_INVALID_COOKIE = '/[\s,;]/';
    		const REGEXP_INVALID_COOKIE = '/[\s;]/'; // ZDEDebuggerPresent cookie is adding commas to the value, so we override the check to not look for commas - MRR20120602
		public function __construct($url=null, $method=null, $config = array()) {
			$defaults = array(
				"ssl_verify_peer"	=>false,	//TODO: FIXME: Dangerous MRR20120601	
				"ssl_verify_host"	=>false,	//TODO: FIXME: Dangerous MRR20120601	
				"connect_timeout"	=>60,
				"adapter"		=>"curl",
			);
			$args = array_replace_recursive($defaults, $config);
			parent::__construct($url, $method, $args);
			// Returns a HTTP_Request2 object
		}

	}

/*************************************** HTTP_Request2_Observer ******************************************/
	// Used to debug communication
	class HTTP_Request2_Observer_All implements \SplObserver
	{
	    protected $dir;

	    protected $fp;

	    public function __construct($dir=null)
	    {
		printf("Constructed");
	    }

	    public function update(\SplSubject $subject)
	    {
		$event = $subject->getLastEvent();
		$output = sprintf("****EVENT: %s=%s\n\n",$event['name'],print_r($event['data'],true));
                print $output;
                error_log($output);
		//printf("******************************************HELLO");
		//printf("****EVENTdump: %s\n\n",print_r($event,true));
		/*
		switch ($event['name']) {
		case 'receivedHeaders':
		    //
		    break;

		case 'receivedBodyPart':
		case 'receivedEncodedBodyPart':
		    fwrite($this->fp, $event['data']);
		    break;

		case 'receivedBody':
		    fclose($this->fp);
		}
		*/
	    }
	}

}

?>
