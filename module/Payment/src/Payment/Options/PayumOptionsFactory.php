<?php
namespace Payment\Options;

use Payment\Storage\CacheableFilesystemStorage;
use Payum\PayumModule\Options\PayumOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Builds PayumOptions at runtime from serializable config arrays.
 * Gateway objects are instantiated here (not in config), so the
 * config cache (var_export) has nothing non-serializable to store.
 */
class PayumOptionsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $payumConfig = $config['payum'];

        $storageDir = $payumConfig['storage_dir'];

        $tokenStorage = new CacheableFilesystemStorage(
            $storageDir,
            'Application\Model\PaymentSecurityToken',
            'hash'
        );

        $gateways = array();
        foreach ($payumConfig['gateways'] as $name => $gatewayConfig) {
            $factoryClass = $gatewayConfig['factory'];
            unset($gatewayConfig['factory']);
            $factory = new $factoryClass();
            $gateways[$name] = $factory->create($gatewayConfig);
        }

        $storages = array(
            'Application\Model\PaymentDetails' => new CacheableFilesystemStorage(
                $storageDir,
                'Application\Model\PaymentDetails',
                'id'
            ),
        );

        return new PayumOptions(array(
            'token_storage' => $tokenStorage,
            'gateways'      => $gateways,
            'storages'      => $storages,
        ));
    }
}
