<?php

namespace MyBatis\Builder\Xml;

use MyBatis\Builder\{
    BaseBuilder,
    BuilderException
};
use MyBatis\DataSource\DataSourceFactoryInterface;
use MyBatis\Executor\Loader\ProxyFactoryInterface;
use MyBatis\Io\Resources;
use MyBatis\Mapping\{
    DatabaseIdProviderInterface,
    Environment,
    EnvironmentBuilder
};
use MyBatis\Parsing\{
    Boolean,
    XNode,
    XPathParser
};
use MyBatis\Plugin\Interceptor;
use MyBatis\Session\{
    AutoMappingBehavior,
    AutoMappingUnknownColumnBehavior,
    Configuration,
    ExecutorType,
    LocalCacheScope
};
use MyBatis\Transaction\TransactionFactoryInterface;
use MyBatis\Type\DbalType;
use Util\Reflection\MetaClass;

class XMLConfigBuilder extends BaseBuilder
{
    private $parsed = false;
    private $parser;
    private $environment;
    private $dirs = [];

    public function __construct(/*XPathParser|mixed*/$dataOrParser, ?string $environment = null, ?array $props = [], ?array $dirs = [])
    {
        if (!($dataOrParser instanceof XPathParser)) {
            $dataOrParser = new XPathParser($dataOrParser, true, $props, new XMLMapperEntityResolver());
        }
        parent::__construct(new Configuration());
        $this->configuration->setVariables($props);
        $this->parsed = false;
        $this->environment = $environment;
        $this->parser = $dataOrParser;
        $this->dirs = $dirs;
    }

    public function parse(): Configuration
    {
        if ($this->parsed) {
            throw new BuilderException("Each XMLConfigBuilder can only be used once.");
        }
        $this->parsed = true;
        $this->parseConfiguration($this->parser->evalNode("/configuration"));
        return $this->configuration;
    }

    private function parseConfiguration(XNode $root): void
    {
        try {
            // issue #117 read properties first
            $this->propertiesElement($root->evalNode("properties"));
            $settings = $this->settingsAsProperties($root->evalNode("settings"));
            //loadCustomVfs(settings);
            //loadCustomLogImpl(settings);
            $this->typeAliasesElement($root->evalNode("typeAliases"));
            $this->pluginElement($root->evalNode("plugins"));
            //objectFactoryElement(root.evalNode("objectFactory"));
            //objectWrapperFactoryElement(root.evalNode("objectWrapperFactory"));
            $this->reflectorFactoryElement($root->evalNode("reflectorFactory"));
            $this->settingsElement($settings);
            // read it after objectFactory and objectWrapperFactory issue #631
            $this->environmentsElement($root->evalNode("environments"));
            $this->databaseIdProviderElement($root->evalNode("databaseIdProvider"));
            $this->typeHandlerElement($root->evalNode("typeHandlers"));
            $this->mapperElement($root->evalNode("mappers"));
        } catch (\Exception $e) {
            throw new BuilderException("Error parsing SQL Mapper Configuration. Cause: " . $e->getMessage());
        }
    }

    private function settingsAsProperties(?XNode $context): array
    {
        if ($context === null) {
            return [];
        }
        $props = $context->getChildrenAsProperties();
        // Check that all settings are known to the configuration class
        $metaConfig = new MetaClass(Configuration::class);
        foreach (array_keys($props) as $key) {
            if (!$metaConfig->hasSetter(strval($key))) {
                throw new BuilderException("The setting " . $key . " is not known.  Make sure you spelled it correctly (case sensitive).");
            }
        }
        return $props;
    }

    /*private void loadCustomVfs(Properties props) throws ClassNotFoundException {
        String value = props->getProperty("vfsImpl");
        if (value != null) {
            String[] clazzes = value.split(",");
            for (String clazz : clazzes) {
                if (!clazz.isEmpty()) {
                    @SuppressWarnings("unchecked")
                    Class<? extends VFS> vfsImpl = (Class<? extends VFS>)Resources.classForName(clazz);
                    $this->configuration->setVfsImpl(vfsImpl);
                }
            }
        }
    }

    private void loadCustomLogImpl(Properties props) {
        Class<? extends Log> logImpl = resolveClass(props->getProperty("logImpl"));
        $this->configuration->setLogImpl(logImpl);
    }*/

    private function typeAliasesElement(?XNode $parent): void
    {
        if ($parent !== null) {
            foreach ($parent->getChildren() as $child) {
                if ("package" == $child->getName()) {
                    $typeAliasPackage = $child->getStringAttribute("name");
                    $configuration->getTypeAliasRegistry()->registerAliases($typeAliasPackage);
                } else {
                    $alias = $child->getStringAttribute("alias");
                    $type = $child->getStringAttribute("type");
                    try {
                        if ($alias === null) {
                            $this->typeAliasRegistry->registerAlias($type);
                        } else {
                            $this->typeAliasRegistry->registerAlias($alias, $type);
                        }
                    } catch (\Exception $e) {
                        throw new BuilderException("Error registering typeAlias for '" . $alias . "'. Cause: " . $e->getMessage());
                    }
                }
            }
        }
    }

    private function pluginElement(?XNode $parent): void
    {
        if ($parent !== null) {
            foreach ($parent->getChildren() as $child) {
                $interceptor = $child->getStringAttribute("interceptor");
                $properties = $child->getChildrenAsProperties();
                $clazz = $this->resolveClass($interceptor);
                $interceptorInstance = new $clazz();
                $interceptorInstance->setProperties($properties);
                $this->configuration->addInterceptor($interceptorInstance);
            }
        }
    }

    /*private function objectFactoryElement(XNode $context): void
    {
        if ($context !== null) {
            String type = $context->getStringAttribute("type");
            Properties properties = $context->getChildrenAsProperties();
            ObjectFactory factory = (ObjectFactory) resolveClass(type)->getDeclaredConstructor().newInstance();
            factory.setProperties(properties);
            $this->configuration->setObjectFactory(factory);
        }
    }

    private void objectWrapperFactoryElement(XNode $context): void{
        if ($context !== null) {
            String type = $context->getStringAttribute("type");
            ObjectWrapperFactory factory = (ObjectWrapperFactory) resolveClass(type)->getDeclaredConstructor().newInstance();
            $this->configuration->setObjectWrapperFactory(factory);
        }
    }*/

    private function reflectorFactoryElement(?XNode $context): void
    {
        if ($context !== null) {
            $type = $context->getStringAttribute("type");
            $clazz = $this->resolveClass($type);
            $factory = new $clazz();
            $this->configuration->setReflectorFactory($factory);
        }
    }

    private function propertiesElement(?XNode $context): void
    {
        if ($context !== null) {
            $defaults = $context->getChildrenAsProperties();
            $resource = $context->getStringAttribute("resource");
            $url = $context->getStringAttribute("url");
            if ($resource !== null && $url !== null) {
                throw new BuilderException("The properties element cannot specify both a URL and a resource based property file reference.  Please specify one or the other.");
            }
            if ($resource !== null) {
                $defaults = array_merge($defaults, Resources::getResourceAsProperties($resource, $this->dirs));
            } elseif ($url !== null) {
                $defaults = array_merge($defaults, Resources::getUrlAsProperties($url, $this->dirs));
            }
            $vars = $this->configuration->getVariables();
            if (!empty($vars)) {
                $defaults = array_merge($defaults, $vars);
            }
            $this->parser->setVariables($defaults);
            $this->configuration->setVariables($defaults);
        }
    }

    private function settingsElement(array $props = []): void
    {
        $this->configuration->setAutoMappingBehavior(constant(AutoMappingBehavior::class . "::" . (array_key_exists("autoMappingBehavior", $props) ? $props["autoMappingBehavior"] : "PARTIAL")));
        $this->configuration->setAutoMappingUnknownColumnBehavior(AutoMappingUnknownColumnBehavior::forCode((array_key_exists("autoMappingBehavior", $props) ? $props["autoMappingUnknownColumnBehavior"] : "NONE")));
        $this->configuration->setCacheEnabled(Boolean::parseBoolean(array_key_exists("cacheEnabled", $props) ? $props["cacheEnabled"] : true));
        $this->configuration->setProxyFactory($this->createInstance(array_key_exists("proxyFactory", $props) ? $props["proxyFactory"] : null));
        $this->configuration->setLazyLoadingEnabled(Boolean::parseBoolean(array_key_exists("lazyLoadingEnabled", $props) ? $props["lazyLoadingEnabled"] : false));
        $this->configuration->setAggressiveLazyLoading(Boolean::parseBoolean(array_key_exists("aggressiveLazyLoadingEnabled", $props) ? $props["aggressiveLazyLoadingEnabled"] : false));
        $this->configuration->setMultipleResultSetsEnabled(Boolean::parseBoolean(array_key_exists("multipleResultSetsEnabled", $props) ? $props["multipleResultSetsEnabled"] : true));
        $this->configuration->setUseColumnLabel(Boolean::parseBoolean(array_key_exists("useColumnLabel", $props) ? $props["useColumnLabel"] : true));
        $this->configuration->setUseGeneratedKeys(Boolean::parseBoolean(array_key_exists("useGeneratedKeys", $props) ? $props["useGeneratedKeys"] : false));
        $this->configuration->setDefaultExecutorType(constant(ExecutorType::class . "::" . (array_key_exists("defaultExecutorType", $props) ? $props["defaultExecutorType"] : "SIMPLE")));
        $this->configuration->setDefaultStatementTimeout(array_key_exists("defaultStatementTimeout", $props) ? intval($props["defaultStatementTimeout"]) : null);
        $this->configuration->setDefaultFetchSize(array_key_exists("defaultFetchSize", $props) ? intval($props["defaultFetchSize"]) : null);
        $this->configuration->setDefaultResultSetType($this->resolveResultSetType(array_key_exists("defaultResultSetType", $props) ? intval($props["defaultResultSetType"]) : null));
        $this->configuration->setMapUnderscoreToCamelCase(Boolean::parseBoolean(array_key_exists("mapUnderscoreToCamelCase", $props) ? $props["mapUnderscoreToCamelCase"] : false));
        $this->configuration->setSafeRowBoundsEnabled(Boolean::parseBoolean(array_key_exists("safeRowBoundsEnabled", $props) ? $props["safeRowBoundsEnabled"] : false));
        $this->configuration->setLocalCacheScope(constant(LocalCacheScope::class . "::" . (array_key_exists("localCacheScope", $props) ? $props["localCacheScope"] : "SESSION")));
        $this->configuration->setDbalTypeForNull(DbalType::forCode(array_key_exists("dbalTypeForNull", $props) ? $props["dbalTypeForNull"] : "OTHER"));
        //$this->configuration->setLazyLoadTriggerMethods(stringSetValueOf(props->getProperty("lazyLoadTriggerMethods"), "equals,clone,hashCode,toString"));
        $this->configuration->setSafeResultHandlerEnabled(Boolean::parseBoolean(array_key_exists("safeResultHandlerEnabled", $props) ? $props["safeResultHandlerEnabled"] : true));
        $this->configuration->setDefaultScriptingLanguage($this->resolveClass(array_key_exists("defaultScriptingLanguage", $props) ? intval($props["defaultScriptingLanguage"]) : null));
        $this->configuration->setDefaultEnumTypeHandler($this->resolveClass(array_key_exists("defaultEnumTypeHandler", $props) ? intval($props["defaultEnumTypeHandler"]) : null));
        $this->configuration->setCallSettersOnNulls(Boolean::parseBoolean(array_key_exists("callSettersOnNulls", $props) ? $props["callSettersOnNulls"] : false));
        $this->configuration->setUseActualParamName(Boolean::parseBoolean(array_key_exists("useActualParamName", $props) ? $props["useActualParamName"] : true));
        $this->configuration->setReturnInstanceForEmptyRow(Boolean::parseBoolean(array_key_exists("returnInstanceForEmptyRow", $props) ? $props["returnInstanceForEmptyRow"] : false));
        //$this->configuration->setLogPrefix(array_key_exists("logPrefix", $props) ? intval($props["logPrefix"]) : "");
        //$this->configuration->setConfigurationFactory(resolveClass(props->getProperty("configurationFactory")));
        $this->configuration->setShrinkWhitespacesInSql(Boolean::parseBoolean(array_key_exists("shrinkWhitespacesInSql", $props) ? $props["shrinkWhitespacesInSql"] : false));
        $this->configuration->setArgNameBasedConstructorAutoMapping(Boolean::parseBoolean(array_key_exists("argNameBasedConstructorAutoMapping", $props) ? $props["argNameBasedConstructorAutoMapping"] : false));
        $this->configuration->setDefaultSqlProviderType($this->resolveClass(array_key_exists("defaultSqlProviderType", $props) ? intval($props["defaultSqlProviderType"]) : null));
        $this->configuration->setNullableOnForEach(Boolean::parseBoolean(array_key_exists("nullableOnForEach", $props) ? $props["nullableOnForEach"] : false));
    }

    private function environmentsElement(?XNode $context): void
    {
        if ($context !== null) {
            if ($this->environment === null) {
                $this->environment = $context->getStringAttribute("default");
            }
            foreach ($context->getChildren() as $child) {
                $id = $child->getStringAttribute("id");
                if ($this->isSpecifiedEnvironment($id)) {
                    $txFactory = $this->transactionManagerElement($child->evalNode("transactionManager"));
                    $dsFactory = $this->dataSourceElement($child->evalNode("dataSource"));
                    $dataSource = $dsFactory->getDataSource();
                    $environmentBuilder = (new EnvironmentBuilder($id))
                        ->transactionFactory($txFactory)
                        ->dataSource($dataSource);
                    $this->configuration->setEnvironment($environmentBuilder->build());
                    break;
                }
            }
        }
    }

    private function databaseIdProviderElement(?XNode $context): void
    {
        $databaseIdProvider = null;
        if ($context !== null) {
            $type = $context->getStringAttribute("type");
            // awful patch to keep backward compatibility
            if ("VENDOR" == $type) {
                $type = "DB_VENDOR";
            }
            $properties = $context->getChildrenAsProperties();
            $clazz = $this->resolveClass($type);
            $databaseIdProvider = new $clazz();
            $databaseIdProvider->setProperties($properties);
        }
        $environment = $this->configuration->getEnvironment();
        if ($environment !== null && $databaseIdProvider !== null) {
            $databaseId = $databaseIdProvider->getDatabaseId($environment->getDataSource());
            $this->configuration->setDatabaseId($databaseId);
        }
    }

    private function transactionManagerElement(XNode $context): TransactionFactoryInterface
    {
        if ($context !== null) {
            $type = $context->getStringAttribute("type");
            $props = $context->getChildrenAsProperties();
            $clazz = $this->resolveClass($type);
            $factory = new $clazz();
            $factory->setProperties($props);
            return $factory;
        }
        throw new BuilderException("Environment declaration requires a TransactionFactory.");
    }

    private function dataSourceElement(XNode $context): DataSourceFactoryInterface
    {
        if ($context !== null) {
            $type = $context->getStringAttribute("type");
            $props = $context->getChildrenAsProperties();
            $clazz = $this->resolveClass($type);
            $factory = new $clazz();
            $factory->setProperties($props);
            return $factory;
        }
        throw new BuilderException("Environment declaration requires a DataSourceFactory.");
    }

    private function typeHandlerElement(?XNode $parent): void
    {
        if ($parent !== null) {
            foreach ($parent->getChildren() as $child) {
                if ("package" == $child->getName()) {
                    $typeHandlerPackage = $child->getStringAttribute("name");
                    $this->typeHandlerRegistry->register($typeHandlerPackage);
                } else {
                    $phpTypeName = $child->getStringAttribute("phpType");
                    $dbalTypeName = $child->getStringAttribute("dbalType");
                    $handlerTypeName = $child->getStringAttribute("handler");
                    $phpTypeClass = $this->resolveClass($phpTypeName);
                    $dbalType = $this->resolveDbalType($dbalTypeName);
                    $typeHandlerClass = $this->resolveClass($handlerTypeName);
                    if ($phpTypeClass !== null) {
                        if ($dbalType === null) {
                            $this->typeHandlerRegistry->register($phpTypeClass, $typeHandlerClass);
                        } else {
                            $this->typeHandlerRegistry->register($phpTypeClass, $dbalType, $typeHandlerClass);
                        }
                    } else {
                        $this->typeHandlerRegistry->register($typeHandlerClass);
                    }
                }
            }
        }
    }

    private function mapperElement(?XNode $parent): void
    {
        if ($parent !== null) {
            foreach ($parent->getChildren() as $child) {
                if ("package" == $child->getName()) {
                    $mapperPackage = $child->getStringAttribute("name");
                    $this->configuration->addMappers($mapperPackage);
                } else {
                    $resource = $child->getStringAttribute("resource");
                    $url = $child->getStringAttribute("url");
                    $mapperClass = $child->getStringAttribute("class");
                    if ($resource !== null && $url === null && $mapperClass === null) {
                        $inputStream = Resources::getResourceAsStream($resource, $this->dirs);
                        $mapperParser = new XMLMapperBuilder($inputStream, $this->configuration, $resource, $this->configuration->getSqlFragments());
                        $mapperParser->parse();
                        //fclose($inputStream);
                    } elseif ($resource === null && $url !== null && $mapperClass === null) {
                        $inputStream = Resources::getUrlAsStream($url, $this->dirs);
                        $mapperParser = new XMLMapperBuilder($inputStream, $this->configuration, $url, $this->configuration->getSqlFragments());
                        $mapperParser->parse();
                        //fclose($inputStream);
                    } elseif ($resource === null && $url === null && $mapperClass !== null) {
                        $this->configuration->addMapper($mapperClass);
                    } else {
                        throw new BuilderException("A mapper element may only specify a url, resource or class, but not more than one.");
                    }
                }
            }
        }
    }

    private function isSpecifiedEnvironment(string $id): bool
    {
        if ($this->environment === null) {
            throw new BuilderException("No environment specified.");
        }
        if ($id === null) {
            throw new BuilderException("Environment requires an id attribute.");
        }
        return $this->environment == $id;
    }
}
