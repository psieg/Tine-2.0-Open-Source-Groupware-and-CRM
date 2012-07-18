<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_User_Abstract
 */
class Tinebase_UserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    protected $_originalBackendConfiguration = null;
    protected $_originalBackendType = null;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_UserTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_originalBackendConfiguration = Tinebase_User::getBackendConfiguration();
        $this->_originalBackendType = Tinebase_User::getConfiguredBackend();
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_Config::getInstance()->clearCache();
        
        // needs to be reverted because we use Tinebase_User as a singleton
        Tinebase_User::setBackendType($this->_originalBackendType);
        Tinebase_User::deleteBackendConfiguration();
        Tinebase_User::setBackendConfiguration($this->_originalBackendConfiguration);
        Tinebase_User::saveBackendConfiguration();
    }

    /**
     * testSaveBackendConfiguration
     */
    public function testSaveBackendConfiguration()
    {
        Tinebase_User::setBackendType(Tinebase_User::LDAP);
     
        $rawConfigBefore = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::USERBACKEND, null, 'null');
        $key = 'host';
        $testValue = 'phpunit-test-host2';
        Tinebase_User::setBackendConfiguration($testValue, $key);
        Tinebase_User::saveBackendConfiguration();
        $rawConfigAfter = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::USERBACKEND);
        $this->assertNotEquals($rawConfigBefore, $rawConfigAfter);
    }
    
    /**
     * testSetBackendConfiguration
     */
    public function testSetBackendConfiguration()
    {
        Tinebase_User::setBackendType(Tinebase_User::LDAP);
     
        $key = 'host';
        $testValue = 'phpunit-test-host';
        Tinebase_User::setBackendConfiguration($testValue, $key);
        $this->assertEquals($testValue, Tinebase_User::getBackendConfiguration($key));

        $testValues = array('host' => 'phpunit-test-host2',
           'username' => 'cn=testcn,ou=teestou,o=testo',
           'password' => 'secret'
        );
        Tinebase_User::setBackendConfiguration($testValues, null);
        foreach ($testValues as $key => $testValue) {
            $this->assertEquals($testValue, Tinebase_User::getBackendConfiguration($key));
        }
    }
    
    /**
     * delete backend config
     */
    public function testDeleteBackendConfiguration()
    {
        Tinebase_User::setBackendType(Tinebase_User::LDAP);
     
        $key = 'host';
        Tinebase_User::setBackendConfiguration('configured-host', $key);

        Tinebase_User::deleteBackendConfiguration($key);
        $this->assertEquals('default-host', Tinebase_User::getBackendConfiguration($key, 'default-host'));
        
        $configOptionsCount = count(Tinebase_User::getBackendConfiguration());
        Tinebase_User::deleteBackendConfiguration('non-existing-key');
        $this->assertEquals($configOptionsCount, count(Tinebase_User::getBackendConfiguration()));
        
        $this->assertTrue($configOptionsCount > 0, 'user backend config should be not empty');
        Tinebase_User::deleteBackendConfiguration();
        $this->assertTrue(count(Tinebase_User::getBackendConfiguration()) == 0, 'should be empty: ' . print_r(Tinebase_User::getBackendConfiguration(), TRUE));
    }
    
    /**
     * get backend cfg defaults
     */
    public function testGetBackendConfigurationDefaults()
    {
        $defaults = Tinebase_User::getBackendConfigurationDefaults();
        $this->assertTrue(array_key_exists(Tinebase_User::SQL, $defaults));
        $this->assertTrue(array_key_exists(Tinebase_User::LDAP, $defaults));
        $this->assertTrue(is_array($defaults[Tinebase_User::LDAP]));
        $this->assertFalse(array_key_exists('host', $defaults));
        
        $defaults = Tinebase_User::getBackendConfigurationDefaults(Tinebase_User::LDAP);
        $this->assertTrue(array_key_exists('host', $defaults));
        $this->assertFalse(array_key_exists(Tinebase_User::LDAP, $defaults));
    }
    
    /**
     * testPasswordPolicy
     * 
     * @see 0003008: add password policies
     * @see 0003978: Option to only allow US-ASCII Charsets (for passwords)
     * @see 0006774: fix empty password handling
     */
    public function testPasswordPolicy()
    {
        // activate pw policies
        $policy = array(
            Tinebase_Config::PASSWORD_POLICY_ACTIVE                 => TRUE,
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII              => TRUE,
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH             => 20,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS         => 4,
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS    => 3,
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS      => 3,
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS            => 3,
        );
        foreach ($policy as $key => $value) {
            Tinebase_Config::getInstance()->set($key, $value);
        }
        
        $sclever = Tinebase_User::getInstance()->getUserByLoginName('sclever');
        
        try {
            Tinebase_User::getInstance()->setPassword($sclever, 'nOve!ry1leverPw2ä');
            $this->fail('Expected Tinebase_Exception_PasswordPolicyViolation');
        } catch (Tinebase_Exception_PasswordPolicyViolation $tppv) {
            $this->assertContains('Password failed to match the following policy requirements: '.
                'pwPolicyOnlyASCII|pwPolicyMinLength|pwPolicyMinUppercaseChars|pwPolicyMinSpecialChars|pwPolicyMinNumbers', $tppv->getMessage());
        }

        try {
            Tinebase_User::getInstance()->setPassword($sclever, '');
            $this->fail('Expected Tinebase_Exception_PasswordPolicyViolation');
        } catch (Tinebase_Exception_PasswordPolicyViolation $tppv) {
            $this->assertContains('Password failed to match the following policy requirements: '.
                'pwPolicyMinLength', $tppv->getMessage());
        }
    }
}
