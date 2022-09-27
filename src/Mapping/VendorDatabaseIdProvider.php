<?php

namespace MyBatis\Mapping;

class VendorDatabaseIdProvider implements DatabaseIdProviderInterface
{
    private $properties;

    public function setProperties(array $p): void
    {
        $this->properties = $p;
    }

    public function getDatabaseId(DataSourceInterface $dataSource): ?string
    {
        $productName = $this->getDatabaseProductName($dataSource);
        if (!empty($this->properties)) {
            foreach ($this->properties as $key => $value) {
                if (strpos($productName, $key) !== false) {
                    return $value;
                }
            }
            // no match, return null
            return null;
        }
        return $productName;
    }

    private function getDatabaseProductName(DataSourceInterface $dataSource): ?string
    {
        $conn = $dataSource->getConnection();

        $platform = $conn->getDatabasePlatform();

        return $platform->getName();    
    }
}
