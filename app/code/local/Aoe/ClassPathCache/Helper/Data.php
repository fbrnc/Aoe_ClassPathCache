<?php

/**
 * Helper
 * 
 * @author Fabrizio Branca
 * @since 2013-05-23
 */
class Aoe_ClassPathCache_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Clear the class path cache
     *
     * @return bool
     */
    public function clearClassPathCache() {
        $result = false;
        if (Varien_Autoload::isApcUsed()) {
            if (php_sapi_name() == 'cli') {
                // do frontend call
                Mage::log('[ClassPathCache] Doing frontend call.');
                $response = file_get_contents($this->getUrl());
                if ($response != 'OK') {
                    $result = false;
                    Mage::log('[ClassPathCache] Frontend call failed. Response: ' . $response);
                } else {
                    $result = true;
                    Mage::log('[ClassPathCache] Frontend call success');
                }
            } else {
                // delete cache directly
                $result = !apc_exists(Varien_Autoload::getCacheKey()) || apc_delete(Varien_Autoload::getCacheKey());
                if ($result) {
                    Mage::log('[ClassPathCache] Delete cache from apc.');
                } else {
                    Mage::log('[ClassPathCache] Deleting cache from apc FAILED.');
                }
            }
        } else {
            $result = unlink(Varien_Autoload::getCacheFilePath());
            if ($result) {
                Mage::log('[ClassPathCache] Delete cache from file system.');
            } else {
                Mage::log('[ClassPathCache] Deleting cache from file system FAILED.');
            }
        }
        return $result;
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function checkUrl()
    {
        $k = base64_decode(Mage::app()->getRequest()->getParam('k'));
        $v = base64_decode(Mage::app()->getRequest()->getParam('v'));
        $ek = Mage::helper('core')->decrypt($v);
        return $k && $v && ($ek == $k);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function getUrl()
    {
        $k = Mage::helper('core')->getRandomString(16);
        return Mage::getUrl('aoeclasspathcache/index/clear', array(
            'k' => base64_encode($k),
            'v' => base64_encode(Mage::helper('core')->encrypt($k)),
            '_store' => 'default' // TODO: that's not nice
        ));
    }


}