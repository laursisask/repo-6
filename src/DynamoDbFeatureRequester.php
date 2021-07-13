<?php
namespace LaunchDarkly\Integrations;

use Aws\DynamoDb\DynamoDbClient;

class DynamoDbFeatureRequester extends FeatureRequesterBase
{
    /** @var string */
    protected $_tableName;
    /** @var string */
    protected $_prefix;
    /** @var DynamoDbClient */
    protected $_client;

    public function __construct($baseUri, $sdkKey, $options)
    {
        parent::__construct($baseUri, $sdkKey, $options);

        if (!isset($options['dynamodb_table'])) {
            throw new \InvalidArgumentException('dynamodb_table must be specified');
        }
        $this->_tableName = $options['dynamodb_table'];

        $dynamoDbOptions = isset($options['dynamodb_options']) ? $options['dynamodb_options'] : array();
        $dynamoDbOptions['version'] = '2012-08-10'; // in the AWS SDK for PHP, this is how you specify the API version
        $this->_client = new DynamoDbClient($dynamoDbOptions);

        $prefix = isset($options['dynamodb_prefix']) ? $options['dynamodb_prefix'] : '';
        $this->_prefix = ($prefix != null && $prefix != '') ? ($prefix . ':') : '';
    }

    protected function readItemString($namespace, $key)
    {
        $request = array(
            'TableName' => $this->_tableName,
            'ConsistentRead' => true,
            'Key' => array(
                'namespace' => array('S' => $this->_prefix . $namespace),
                'key' => array('S' => $key)
            )
        );
        $result = $this->_client->getItem($request);
        if (!$result) {
            return null;
        }
        $item = $result->get('Item');
        if (!$item || !isset($item['item'])) {
            return null;
        }
        $attr = $item['item'];
        return isset($attr['S']) ? $attr['S'] : null;
    }

    protected function readItemStringList($namespace)
    {
        $items = array();
        $request = array(
            'TableName' => $this->_tableName,
            'ConsistentRead' => true,
            'KeyConditions' => array(
                'namespace' => array(
                    'ComparisonOperator' => 'EQ',
                    'AttributeValueList' => array(array('S' => $this->_prefix . $namespace))
                )
            )
        );
        // We may need to repeat this query several times due to pagination
        $moreItems = true;
        while ($moreItems) {
            $result = $this->_client->query($request);
            foreach ($result->get('Items') as $item) {
                if (isset($item['item'])) {
                    $attr = $item['item'];
                    if (isset($attr['S'])) {
                        $items[] = $attr['S'];
                    }
                }
            }
            if (isset($result['LastEvaluatedKey']) && $result['LastEvaluatedKey']) {
                $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
                $moreItems = false;
            }
        }
        return $items;
    }
}

class DynamoDb
{
    /**
     * Configures an adapter for reading feature flag data from DynamoDB.
     *
     * To use this method, you must have installed the package `aws/aws-sdk-php`. After calling this
     * method, store its return value in the `feature_requester` property of your client configuration:
     *
     *     $fr = LaunchDarkly\Integrations\DynamoDb::featureRequester([ "dynamodb_table" => "my-table" ]);
     *     $config = [ "feature_requester" => $fr ];
     *     $client = new LDClient("sdk_key", $config);
     *
     * For more about using LaunchDarkly with databases, see the
     * [SDK reference guide](https://docs.launchdarkly.com/v2.0/docs/using-a-persistent-feature-store).
     *
     * @param array $options  Configuration settings (can also be passed in the main client configuration):
     *   - `dynamodb_table`: (required) name of an existing table in DynamoDB.
     *   - `dynamodb_options`: can include any settings supported by the AWS SDK client
     *   - `dynamodb_prefix`: a string to be prepended to all database keys; corresponds to the prefix
     * setting in ld-relay
     *   - `apc_expiration`: expiration time in seconds for local caching, if `APCu` is installed
     * @return mixed  an object to be stored in the `feature_requester` configuration property
     */
    public static function featureRequester($options = array())
    {
        return function ($baseUri, $sdkKey, $baseOptions) use ($options) {
            return new DynamoDbFeatureRequester($baseUri, $sdkKey, array_merge($baseOptions, $options));
        };
    }
}
    