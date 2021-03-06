<?php
/**
 * user
 * 
 * @author pagliaccio
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';
class Model_user extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = USERS_TABLE;
    protected $_primary = 'ID';
    /**
     * model_option
     * @var Model_option
     */
    public $option = null;
    public $data = null;
    public $ID;
    static private $instance;
    /**
     * @param mixed $option
     */
    function __construct ($option)
    {
    	//$this->_name=PREFIX.'user';
    	parent::__construct();
    	if (is_int($option)) $query="`ID`='$option'";
    	elseif (is_array($option)) $query=$option;
    	else throw new Zend_Db_Table_Exception(' $option('.print_r($option,true).') params is not int or array');
    	$this->data= $this->fetchRow($query);
    	$ID=intval($this->data['ID']);
    	$this->ID=$ID;
    	self::$instance=$this;
    	$this->option=new Model_option($ID);
    }
    static function getInstance($option=0) {
    	if (self::$instance) return self::$instance;
    	else return new Model_User($option);
    }
    /**
     * registra un utente, ritorna true se la registrazione &egrave; andata bene.
     * @param Array $vect indice per il campo e valore come valore 
     * @return bool
     */
    static function register ($data)
	{
		if ($data) {
			$data['code_time']=time();
			self::getDefaultAdapter()->insert(USERS_TABLE,$data);
			return true;
		} else {
			return false;
		}
	}
    /**
     * modifica i valori dell'utente
     * @param Array $data indice per il campo e valore come valore 
     * @return bool 
     */
    function updateU ($data)
    {
        if ($data) {
        	if (isset($data['code'])) {
        		$data['code_time']=time();
        	}
            return $this->update($data,"`ID`='$this->ID'");
        }
        return false;
    }
    /**
     * ritorna la lista dei server in cui è iscritto un utente
     */
    function getServerSubscrive() {
    	$sel=$this->getDefaultAdapter()->select();
        $sel->from(RELATION_USER_CIV_TABLE)->where("user_id = ?",$this->ID);
    	return $this->getDefaultAdapter()->fetchAll($sel);
    }
    
    function getCiv($server) {
    	$sel=$this->getAdapter()->select();
    	$where="`user_id`='".$this->ID."' AND `server`='".$server."'";
        $sel->from(RELATION_USER_CIV_TABLE)->where($where);
        $cid=$this->getAdapter()->fetchRow($sel);
    	return $cid;
    }
}
