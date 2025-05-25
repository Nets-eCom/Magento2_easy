<?php

namespace Nexi\Checkout\Setup\Patch\Data;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InstallProfilesPatch implements
    DataPatchInterface
{
    const DEFAULT_PROFILE_DATA = [
        [
            'name' => 'Weekly',
            'schedule' => [
                'interval' => 1,
                'unit' => 'W'
            ]
        ],
        [
            'name' => 'Monthly',
            'schedule' => [
                'interval' => 1,
                'unit' => 'M'
            ]
        ],
    ];
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addDefaultProfiles();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    private function addDefaultProfiles()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $this->moduleDataSetup->getConnection()->insertMultiple(
            $connection->getTableName('recurring_payment_profiles'),
            $this->getProfileData()
        );
    }

    private function getProfileData()
    {
        $data = [];

        foreach (self::DEFAULT_PROFILE_DATA as $profile) {
            $profile['schedule'] = $this->serializer->serialize($profile['schedule']);
            $data[] = $profile;
        }

        return $data;
    }
}
