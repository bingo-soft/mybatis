<?php

namespace MyBatis\Builder\Xml;

use MyBatis\Io\Resources;
use Sax\EntityResolverInterface;

class XMLMapperEntityResolver implements EntityResolverInterface
{
    private const IBATIS_CONFIG_SYSTEM = "ibatis-3-config.dtd";
    private const IBATIS_MAPPER_SYSTEM = "ibatis-3-mapper.dtd";
    private const MYBATIS_CONFIG_SYSTEM = "mybatis-3-config.dtd";
    private const MYBATIS_MAPPER_SYSTEM = "mybatis-3-mapper.dtd";

    private const MYBATIS_CONFIG_DTD = "org/apache/ibatis/builder/xml/mybatis-3-config.dtd";
    private const MYBATIS_MAPPER_DTD = "org/apache/ibatis/builder/xml/mybatis-3-mapper.dtd";

    /**
     * Converts a public DTD into a local one.
     *
     * @param publicId
     *          The public id that is what comes after "PUBLIC"
     * @param systemId
     *          The system id that is what comes after the public id.
     * @return The InputSource for the DTD
     */
    public function resolveEntity(string $publicId, ?string $systemId = null)
    {
        try {
            if ($systemId !== null) {
                $lowerCaseSystemId = strtolower($systemId);
                if (strpos($lowerCaseSystemId, self::MYBATIS_CONFIG_SYSTEM) !== false || strpos($lowerCaseSystemId, self::IBATIS_CONFIG_SYSTEM) !== false) {
                    return $this->getInputSource(self::MYBATIS_CONFIG_DTD, $publicId, $systemId);
                } elseif (strpos($lowerCaseSystemId, self::MYBATIS_MAPPER_SYSTEM) !== false || strpos($lowerCaseSystemId, self::IBATIS_MAPPER_SYSTEM) !== false) {
                    return $this->getInputSource(self::MYBATIS_MAPPER_DTD, $publicId, $systemId);
                }
            }
            return null;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function getInputSource(?string $path, string $publicId, string $systemId)
    {
        $source = null;
        if ($path !== null) {
            try {
                $source = Resources::getResourceAsStream($path);
            } catch (\Throwable $e) {
                // ignore, null is ok
            }
        }
        return $source;
    }
}
