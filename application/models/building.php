<?php
/**
 * building
 * 
 * @author pagliaccio
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';
class Model_building extends Zend_Db_Table_Abstract
{
    var $data = array();
    static $name = array(
    array("", "focolare", "caverna dello sciamano", "dispensa", 
    "cava di pietra", "allevamento", "segheria", "palafitte", 
    "tenda del mercante", "barrack","command","research"), 
    array("", "foro", "salariato", "magazzino imperiale", "saline", "fonderia", 
    "segheria", "insulae", "bazar", "Castrum","senato","tempio"), 
    array("", "municipio", "forziere reale", "magazzino reale", "miniera d'oro", 
    "fucina", "fabbrica di mattoni", "borgo", "market", "barrack","reggia","convento"), 
    array("", "cantiere", "banca", "magazzino industriale", "ufficio tasse", 
    "miniera di carbone", "cementificio", "case", "market", "barrack","palazzo imperiale","universit&agrave;"), 
    array("", "impresa edile", "banca centrale", "magazzino robotico", 
    "ministero dell'economia", "pozzo di petrolio", "impianto metallurgico", 
    "grattacieli", "wall street", "barrack","parlamento","centro di ricerca"), 
    array("", "nanocostruttore", "nanostorage", "storage2", "nanoforgia", 
    "prod2", "prod3", "abissi d'acciaio", "teletrasportatore", "barrack","Deep Thought","laboratorio robotico"));
    /**
     * traduttore
     * @var Zend_Translate
     */
    private $t;
    private $village_id;
    private $civ_id;
    private $log;
    /**
     * estrae dal DB gli edifici del villaggio
     * @param int $civ_id
     * @param int $village_id
     */
    function __construct ($civ_id, $village_id)
    {
    	$this->_name=SERVER.'_building';
    	parent::__construct();
    	$this->log=Zend_Registry::get("log");
        $this->civ_id = $civ_id;
        $this->village_id = $village_id;
        $this->getAdapter()->setFetchMode(Zend_Db::FETCH_ASSOC);
        $data=$this->fetchAll("`village_id`='" . $village_id ."'","pos");
        foreach ($data as $value) {
        	$this->data[$value['pos']]=$value;
        }
        $this->t = Zend_Registry::get("translate");
    }
    /**
     * restituisce un arrai con le proprietà di una struttura
     * @param int $pos >0
     * @return Array  liv,type,rid,cost,time,capacity,prod,maxliv
     */
    function getproperty ($pos, $age, $type = false)
    {
        global $Building_Array;
        $AgeBonus = $age + 1;
        $pop=isset($this->data[$pos])? $this->data[$pos]['pop'] :0;
        $prop = array('liv' => $this->getLiv($pos), 
        'type' => $this->getType($pos),'pop'=>$pop);
        if (! $type)
            $type = $prop['type']-1;
        switch ($type+1) {
            case MAIN:
                $prop['rid'] = main::$rid[$prop['liv']];
                break;
            case STORAGE1:
                $prop['capacity'] = storage1::$capacity[$prop['liv']] * $AgeBonus;
                break;
            case STORAGE2:
                $prop['capacity'] = storage2::$capacity[$prop['liv']];
                break;
            case PROD1:
                $prop['prod'] = prod1::$prod[$prop['liv']] * $AgeBonus;
                break;
            case PROD2:
                $prop['prod'] = prod2::$prod[$prop['liv']] * $AgeBonus;
                break;
            case PROD3:
                $prop['prod'] = prod3::$prod[$prop['liv']] * $AgeBonus;
                break;
            case HOUSE:
                $prop['capacity'] = house::$capacity[$prop['liv']] * $AgeBonus;
                break;
            case MARKET:
            	
                break;
            case BARRACK:
                $prop['rid'] = barrack::$rid[$prop['liv']];
                
        }
        $prop['maxpop'] = $Building_Array[$type]::$maxPop[$age] * $AgeBonus;
        $prop['maxliv'] = $Building_Array[$type]::$maxliv[$age];
        $prop['cost'] = $Building_Array[$type]::$cost[$prop['liv'] + 1];
        $cost = $prop['cost'][0] + $prop['cost'][1] + $prop['cost'][2];
        $prop['time'] = intval($cost / $this->getMainBuildingReduction() * 3600);
        return $prop;
    }
    /**
     * restituisce la capacità totale
     * @param int $storagetype
     * @return int
     */
    function getCapTot ($storagetype = STORAGE1)
    {
        global $Building_Array;
        $build = $this->data;
        $storage = 0;
        for ($i = 0; $i <= TOTBUILDING; $i ++) {
            if ($build[$i]['type'] == $storagetype) {
                $liv = $this->getLiv($i);
                $storage += $Building_Array[$storagetype - 1]::$capacity[$liv];
            }
        }
        if ($storage == 0) {
            if ($storagetype == STORAGE1) {
                $storage = storage1::$capacity[0];
            } elseif ($storagetype == STORAGE2) {
                $storage = storage2::$capacity[0];
            } else {
                $storage = house::$capacity[0];
            }
        }
        return $storage;
    }
    /**
     * ritorna la riduzione del main building del villaggio selezionato
     * @return float
     */
    function getMainBuildingReduction ()
    {
        return main::$rid[$this->getLiv(0)];
    }
    /**
     * aggiunge una struttura in coda
     * @param int $time
     * @param int $type
     * @param int $pos
     * 
     */
    function addQueue ($time, $type, $pos, $now)
    {
        $finish = time() + $time;
        //$finish = date("Y-m-d H:i:s",$finish);
        $param = array('pos' => $pos, 'village_id' => $now, 
        'type' => $type);
        $params = serialize($param);
        $this->getDefaultAdapter()->query(
        "INSERT INTO `" . EVENTS_TABLE . "` SET `time`='" . $finish .
         "' , `type`='1', `params`='" . $params . "'");
    }
    /**
     * ritorna il livello della struttura
     * @param int $pos
     * @return int
     */
    function getLiv ($pos)
    {
        return isset($this->data[$pos]) ?$this->data[$pos]['liv']:0;
    }
    /**
     * ritorna i tipo della struttura
     * @param int $pos
     * @return int
     */
    function getType ($pos)
    {
        return (isset($this->data[$pos]) ? $this->data[$pos]['type'] : 0);
    }
    /**
     * fornisce gli edifici che si possono costruire
     * @param int $age
     * @return Array
     */
    function getDispBuilding ($age)
    {
        global $Building_Array;
        $arrayBuilding = null;
        for ($i = 0; $i < TOT_TYPE_BUILING; $i ++) {
            $bool = true;
            $build = $this->data;
            $existing = false;
            $exit = false;
            $pos = 0;
            // controllo esistenza
            for ($po = 0; ($po <= TOTBUILDING) && (! $exit); $po ++)
                if ($build[$po]['type'] == ($i + 1)) {
                    $existing = true;
                    $pos = $po;
                    if ($this->getLiv($po) == "20")
                        $exit = true;
                }
            if (($existing) && ! (($Building_Array[$i]::$multiple_at_level20) && ($this->getLiv(
            $pos) == "20") || $Building_Array[$i]::$multiple))
                $bool = false;
                 // controllo requisiti
            if ($Building_Array[$i]::$require != null) {
                //$require[$n]['type'] $require[$n]['liv'] $require['age']
                if ($Building_Array[$i]::$require['age'] <= $age) {
                    $req = $Building_Array[$i]::$require;
                    for ($r = 0; ($req[$r]) && ($bool); $r ++) {
                        $p = $this->getBildForType($req[$r]['type']);
                        if ($p >= 0) {
                            if ($this->getLiv($p) < $req[$r]['liv'])
                                $bool = false;
                        } else
                            $bool = false;
                    }
                } else
                    $bool = false;
            }
            //  per altri controlli , se è false si mette $bool=false
            //se supera tutti i controlli allora lo aggiungiamo all'array
                $arrayBuilding[$i + 1] = $bool;
        }
        return $arrayBuilding;
    }
    /**
     * controlla se l'edificio è costruibile
     * @param Array $costArray
     * @param int liv
     * @param int max
     * @return bool
     */
    function canBuild ($costArray, $resource, $liv, $pos,$type,$age, $max = 20)
    {
        $bool = true;
        for ($i = 0; ($i < 3) && ($bool); $i ++) {
            if ($costArray[$i] > $resource[$i])
                $bool = false;
        }
        if (! $bool)
            $mess = $this->t->_("non hai le risorse necessarie!");
        if ($costArray[3] > ($resource[3] - $resource[4])) {
            $mess = $this->t->_("non hai abbastanza popolazione!");
            $bool = false;
        }
        if ($this->getDefaultAdapter()->fetchOne(
        "SELECT count(*) FROM `" . EVENTS_TABLE . "` WHERE `params` LIKE '%\"pos\";i:$pos%\"village_id\";i:".$this->village_id."%' AND `type`='" . BILD_EVENT . "'")) {
            $bool = false;
            $mess = $this->t->_("l'edificio &egrave; in costruzione");
        }
        if ($liv >= $max) {
            $bool = false;
            $mess = $this->t->_("l'edificio &egrave; al livello massimo");
        }
        if ($type<1) $disp[$type]=true;
        else
        $disp=$this->getDispBuilding($age);
        $bool=($bool && $disp[$type]);
        
        
        return array('bool' => $bool, 'mess' => $mess);
    }
    /**
     * ritorna la posizione del edificio cercato
     * @param int $type
     * @return int 
     */
    function getBildForType ($type)
    {
        $pos = - 1;
        for ($po = 0; ($po <= TOTBUILDING) && ($pos < 0); $po ++)
            if ($this->getType($po) == $type)
                $pos = $po;
        return $pos;
    }
}
