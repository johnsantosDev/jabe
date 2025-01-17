<?php

namespace Jabe\Impl\Db\Sql;

use Doctrine\DBAL\Connection;
use Jabe\Impl\Cfg\IdGeneratorInterface;
use Jabe\Impl\Db\DbEntityInterface;
use Jabe\Impl\Interceptor\{
    SessionInterface,
    SessionFactoryInterface
};
use Jabe\Impl\Util\ClassNameUtil;
use MyBatis\Session\SqlSessionFactoryInterface;

class DbSqlSessionFactory implements SessionFactoryInterface
{
    public const MYSQL = "mysql";
    public const POSTGRES = "postgres";
    public const MARIADB = "mariadb";

    public const SUPPORTED_DATABASES = [ self::MYSQL, self::POSTGRES, self::MARIADB ];

    protected static $databaseSpecificStatements = [];

    public static $databaseSpecificLimitBeforeStatements = [];
    public static $databaseSpecificLimitAfterStatements = [];
    //limit statements that can be used to select first N rows without OFFSET
    public static $databaseSpecificLimitBeforeWithoutOffsetStatements = [];
    public static $databaseSpecificLimitAfterWithoutOffsetStatements = [];
    // limitAfter statements that can be used with subqueries
    public static $databaseSpecificInnerLimitAfterStatements = [];
    public static $databaseSpecificLimitBetweenStatements = [];
    public static $databaseSpecificLimitBetweenFilterStatements = [];
    public static $databaseSpecificLimitBetweenAcquisitionStatements = [];
    // count distinct statements
    public static $databaseSpecificCountDistinctBeforeStart = [];
    public static $databaseSpecificCountDistinctBeforeEnd = [];
    public static $databaseSpecificCountDistinctAfterEnd = [];

    public static $optimizeDatabaseSpecificLimitBeforeWithoutOffsetStatements = [];
    public static $optimizeDatabaseSpecificLimitAfterWithoutOffsetStatements = [];

    public static $databaseSpecificEscapeChar = [];
    public static $databaseSpecificOrderByStatements = [];
    public static $databaseSpecificLimitBeforeNativeQueryStatements = [];
    public static $databaseSpecificBitAnd1 = [];
    public static $databaseSpecificBitAnd2 = [];
    public static $databaseSpecificBitAnd3 = [];

    public static $databaseSpecificDatepart1 = [];
    public static $databaseSpecificDatepart2 = [];
    public static $databaseSpecificDatepart3 = [];

    public static $databaseSpecificDummyTable = [];

    public static $databaseSpecificIfNull = [];

    public static $databaseSpecificTrueConstant = [];
    public static $databaseSpecificFalseConstant = [];

    public static $databaseSpecificDistinct = [];

    public static $databaseSpecificNumericCast = [];

    public static $dbSpecificConstants = [];

    public static $databaseSpecificDaysComparator = [];

    public static $databaseSpecificCollationForCaseSensitivity = [];

    public static $databaseSpecificAuthJoinStart = [];
    public static $databaseSpecificAuthJoinEnd = [];
    public static $databaseSpecificAuthJoinSeparator = [];

    public static $databaseSpecificAuth1JoinStart = [];
    public static $databaseSpecificAuth1JoinEnd = [];
    public static $databaseSpecificAuth1JoinSeparator = [];

    private static $initialized = false;

    /*
     * On SQL server, the overall maximum number of parameters in a prepared statement
     * is 2100.
     */
    public const MAXIMUM_NUMBER_PARAMS = 2000;

    public static $defaultOrderBy = 'order by ${internalOrderBy}';

    public static $defaultEscapeChar = "'\\'";

    public static $defaultDistinctCountBeforeStart = "select count(distinct";
    public static $defaultDistinctCountBeforeEnd = ")";
    public static $defaultDistinctCountAfterEnd = "";

    public static $defaultAuthOnStart = "IN (";
    public static $defaultAuthOnEnd = ")";
    public static $defaultAuthOnSeparator = ",";

    protected $databaseType;
    protected $databaseTablePrefix = "";
    protected $databaseSchema;
    protected $sqlSessionFactory;
    protected $idGenerator;
    protected $statementMappings = [];
    protected $insertStatements = [];
    protected $updateStatements = [];
    protected $deleteStatements = [];
    protected $selectStatements = [];
    protected bool $isDbIdentityUsed = true;
    protected bool $isDbHistoryUsed = true;
    protected bool $cmmnEnabled = false;
    protected bool $dmnEnabled = false;

    protected $batchProcessing;

    public function __construct(bool $batchProcessing = false)
    {
        self::init();
        $this->batchProcessing = $batchProcessing;
    }

    public static function init(): void
    {
        if (self::$initialized == false) {
            self::$initialized = true;
            //Postgresql
            self::$databaseSpecificLimitBeforeStatements[self::POSTGRES] = "";
            self::$optimizeDatabaseSpecificLimitBeforeWithoutOffsetStatements[self::POSTGRES] = "";
            self::$databaseSpecificLimitAfterStatements[self::POSTGRES] = "LIMIT #{maxResults} OFFSET #{firstResult}";
            self::$optimizeDatabaseSpecificLimitAfterWithoutOffsetStatements[self::POSTGRES] = "LIMIT #{maxResults}";
            self::$databaseSpecificLimitBeforeWithoutOffsetStatements[self::POSTGRES] = "";
            self::$databaseSpecificLimitAfterWithoutOffsetStatements[self::POSTGRES] = "LIMIT #{maxResults}";
            self::$databaseSpecificInnerLimitAfterStatements[self::POSTGRES] = self::$databaseSpecificLimitAfterStatements[self::POSTGRES];
            self::$databaseSpecificLimitBetweenStatements[self::POSTGRES] = "";
            self::$databaseSpecificLimitBetweenFilterStatements[self::POSTGRES] = "";
            self::$databaseSpecificLimitBetweenAcquisitionStatements[self::POSTGRES] = "";
            self::$databaseSpecificOrderByStatements[self::POSTGRES] = self::$defaultOrderBy;
            self::$databaseSpecificLimitBeforeNativeQueryStatements[self::POSTGRES] = "";
            self::$databaseSpecificDistinct[self::POSTGRES] = "distinct";

            self::$databaseSpecificCountDistinctBeforeStart[self::POSTGRES] = 'SELECT COUNT(*) FROM (SELECT DISTINCT';
            self::$databaseSpecificCountDistinctBeforeEnd[self::POSTGRES] = "";
            self::$databaseSpecificCountDistinctAfterEnd[self::POSTGRES] = ") countDistinct";

            self::$databaseSpecificEscapeChar[self::POSTGRES] = self::$defaultEscapeChar;

            self::$databaseSpecificBitAnd1[self::POSTGRES] = "";
            self::$databaseSpecificBitAnd2[self::POSTGRES] = " & ";
            self::$databaseSpecificBitAnd3[self::POSTGRES] = "";
            self::$databaseSpecificDatepart1[self::POSTGRES] = "extract(";
            self::$databaseSpecificDatepart2[self::POSTGRES] = " from ";
            self::$databaseSpecificDatepart3[self::POSTGRES] = ")";

            self::$databaseSpecificDummyTable[self::POSTGRES] = "";
            self::$databaseSpecificTrueConstant[self::POSTGRES] = "true";
            self::$databaseSpecificFalseConstant[self::POSTGRES] = "false";
            self::$databaseSpecificIfNull[self::POSTGRES] = "COALESCE";

            self::$databaseSpecificCollationForCaseSensitivity[self::POSTGRES] = "";
            self::$databaseSpecificAuthJoinStart[self::POSTGRES] = self::$defaultAuthOnStart;
            self::$databaseSpecificAuthJoinEnd[self::POSTGRES] = self::$defaultAuthOnEnd;
            self::$databaseSpecificAuthJoinSeparator[self::POSTGRES] = self::$defaultAuthOnSeparator;
            self::$databaseSpecificAuth1JoinStart[self::POSTGRES] = self::$defaultAuthOnStart;
            self::$databaseSpecificAuth1JoinEnd[self::POSTGRES] = self::$defaultAuthOnEnd;
            self::$databaseSpecificAuth1JoinSeparator[self::POSTGRES] = self::$defaultAuthOnSeparator;

            self::addDatabaseSpecificStatement(self::POSTGRES, "insertByteArray", "insertByteArray_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "updateByteArray", "updateByteArray_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectByteArray", "selectByteArray_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectByteArrays", "selectByteArrays_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourceByDeploymentIdAndResourceName", "selectResourceByDeploymentIdAndResourceName_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourceByDeploymentIdAndResourceNames", "selectResourceByDeploymentIdAndResourceNames_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourceByDeploymentIdAndResourceId", "selectResourceByDeploymentIdAndResourceId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourceByDeploymentIdAndResourceIds", "selectResourceByDeploymentIdAndResourceIds_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourcesByDeploymentId", "selectResourcesByDeploymentId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectResourceById", "selectResourceById_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectLatestResourcesByDeploymentName", "selectLatestResourcesByDeploymentName_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "insertIdentityInfo", "insertIdentityInfo_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "updateIdentityInfo", "updateIdentityInfo_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectIdentityInfoById", "selectIdentityInfoById_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectIdentityInfoByUserIdAndKey", "selectIdentityInfoByUserIdAndKey_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectIdentityInfoByUserId", "selectIdentityInfoByUserId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectIdentityInfoDetails", "selectIdentityInfoDetails_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "insertComment", "insertComment_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectCommentsByTaskId", "selectCommentsByTaskId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectCommentsByProcessInstanceId", "selectCommentsByProcessInstanceId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectCommentByTaskIdAndCommentId", "selectCommentByTaskIdAndCommentId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectEventsByTaskId", "selectEventsByTaskId_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectFilterByQueryCriteria", "selectFilterByQueryCriteria_postgres");
            self::addDatabaseSpecificStatement(self::POSTGRES, "selectFilter", "selectFilter_postgres");

            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteAttachmentsByRemovalTime", "deleteAttachmentsByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteCommentsByRemovalTime", "deleteCommentsByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricActivityInstancesByRemovalTime", "deleteHistoricActivityInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricDecisionInputInstancesByRemovalTime", "deleteHistoricDecisionInputInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricDecisionInstancesByRemovalTime", "deleteHistoricDecisionInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricDecisionOutputInstancesByRemovalTime", "deleteHistoricDecisionOutputInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricDetailsByRemovalTime", "deleteHistoricDetailsByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteExternalTaskLogByRemovalTime", "deleteExternalTaskLogByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricIdentityLinkLogByRemovalTime", "deleteHistoricIdentityLinkLogByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricIncidentsByRemovalTime", "deleteHistoricIncidentsByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteJobLogByRemovalTime", "deleteJobLogByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricProcessInstancesByRemovalTime", "deleteHistoricProcessInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricTaskInstancesByRemovalTime", "deleteHistoricTaskInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricVariableInstancesByRemovalTime", "deleteHistoricVariableInstancesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteUserOperationLogByRemovalTime", "deleteUserOperationLogByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteByteArraysByRemovalTime", "deleteByteArraysByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteHistoricBatchesByRemovalTime", "deleteHistoricBatchesByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteAuthorizationsByRemovalTime", "deleteAuthorizationsByRemovalTime_postgres_or_db2");
            self::addDatabaseSpecificStatement(self::POSTGRES, "deleteTaskMetricsByRemovalTime", "deleteTaskMetricsByRemovalTime_postgres_or_db2");

            $constants = [];
            $constants["constant.event"] = "'event'";
            $constants["constant.op_message"] = "NEW_VALUE_ || '_|_' || PROPERTY_";
            $constants["constant_for_update"] = "for update";
            $constants["constant.datepart.quarter"] = "QUARTER";
            $constants["constant.datepart.month"] = "MONTH";
            $constants["constant.datepart.minute"] = "MINUTE";
            $constants["constant.null.startTime"] = "null START_TIME_";
            $constants["constant.varchar.cast"] = 'cast(\'${key}\' as varchar(64))';
            $constants["constant.integer.cast"] = "cast(NULL as integer)";
            $constants["constant.null.reporter"] = "CAST(NULL AS VARCHAR) AS REPORTER_";
            self::$dbSpecificConstants[self::POSTGRES] = $constants;

            self::$databaseSpecificDaysComparator[self::POSTGRES] = 'EXTRACT (DAY FROM #{currentTimestamp} - ${date}) >= ${days}';
            self::$databaseSpecificNumericCast[self::POSTGRES] = "";

            //Mysql and MariaDb
            foreach ([self::MYSQL, self::MARIADB] as $mysqlLikeDatabase) {
                self::$databaseSpecificLimitBeforeStatements[$mysqlLikeDatabase] = "";
                self::$optimizeDatabaseSpecificLimitBeforeWithoutOffsetStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificLimitAfterStatements[$mysqlLikeDatabase] = "LIMIT #{maxResults} OFFSET #{firstResult}";
                self::$optimizeDatabaseSpecificLimitAfterWithoutOffsetStatements[$mysqlLikeDatabase] = "LIMIT #{maxResults}";
                self::$databaseSpecificLimitBeforeWithoutOffsetStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificLimitAfterWithoutOffsetStatements[$mysqlLikeDatabase] = "LIMIT #{maxResults}";
                self::$databaseSpecificInnerLimitAfterStatements[$mysqlLikeDatabase] = self::$databaseSpecificLimitAfterStatements[$mysqlLikeDatabase];
                self::$databaseSpecificLimitBetweenStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificLimitBetweenFilterStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificLimitBetweenAcquisitionStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificOrderByStatements[$mysqlLikeDatabase] = self::$defaultOrderBy;
                self::$databaseSpecificLimitBeforeNativeQueryStatements[$mysqlLikeDatabase] = "";
                self::$databaseSpecificDistinct[$mysqlLikeDatabase] = "distinct";
                self::$databaseSpecificNumericCast[$mysqlLikeDatabase] = "";

                self::$databaseSpecificCountDistinctBeforeStart[$mysqlLikeDatabase] = self::$defaultDistinctCountBeforeStart;
                self::$databaseSpecificCountDistinctBeforeEnd[$mysqlLikeDatabase] = self::$defaultDistinctCountBeforeEnd;
                self::$databaseSpecificCountDistinctAfterEnd[$mysqlLikeDatabase] = self::$defaultDistinctCountAfterEnd;

                self::$databaseSpecificEscapeChar[$mysqlLikeDatabase] = "'\\\\'";

                self::$databaseSpecificBitAnd1[$mysqlLikeDatabase] = "";
                self::$databaseSpecificBitAnd2[$mysqlLikeDatabase] = " & ";
                self::$databaseSpecificBitAnd3[$mysqlLikeDatabase] = "";
                self::$databaseSpecificDatepart1[$mysqlLikeDatabase] = "";
                self::$databaseSpecificDatepart2[$mysqlLikeDatabase] = "(";
                self::$databaseSpecificDatepart3[$mysqlLikeDatabase] = ")";

                self::$databaseSpecificDummyTable[$mysqlLikeDatabase] = "";
                self::$databaseSpecificTrueConstant[$mysqlLikeDatabase] = "1";
                self::$databaseSpecificFalseConstant[$mysqlLikeDatabase] = "0";
                self::$databaseSpecificIfNull[$mysqlLikeDatabase] = "IFNULL";

                self::$databaseSpecificDaysComparator[$mysqlLikeDatabase] = 'DATEDIFF(#{currentTimestamp}, ${date}) >= ${days}"';

                self::$databaseSpecificCollationForCaseSensitivity[$mysqlLikeDatabase] = "";
 
                self::$databaseSpecificAuthJoinStart[$mysqlLikeDatabase] = "=";
                self::$databaseSpecificAuthJoinEnd[$mysqlLikeDatabase] = "";
                self::$databaseSpecificAuthJoinSeparator[$mysqlLikeDatabase] = "OR AUTH.RESOURCE_ID_ =";

                self::$databaseSpecificAuth1JoinStart[$mysqlLikeDatabase] = "=";
                self::$databaseSpecificAuth1JoinEnd[$mysqlLikeDatabase] = "";
                self::$databaseSpecificAuth1JoinSeparator[$mysqlLikeDatabase] = "OR AUTH1.RESOURCE_ID_ =";

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "toggleForeignKey", "toggleForeignKey_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "selectDeploymentsByQueryCriteria", "selectDeploymentsByQueryCriteria_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "selectDeploymentCountByQueryCriteria", "selectDeploymentCountByQueryCriteria_mysql");

                // related to CAM-8064
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteExceptionByteArraysByIds", "deleteExceptionByteArraysByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteErrorDetailsByteArraysByIds", "deleteErrorDetailsByteArraysByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricDetailsByIds", "deleteHistoricDetailsByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricDetailByteArraysByIds", "deleteHistoricDetailByteArraysByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricIdentityLinksByTaskProcessInstanceIds", "deleteHistoricIdentityLinksByTaskProcessInstanceIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricIdentityLinksByTaskCaseInstanceIds", "deleteHistoricIdentityLinksByTaskCaseInstanceIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricDecisionInputInstanceByteArraysByDecisionInstanceIds", "deleteHistoricDecisionInputInstanceByteArraysByDecisionInstanceIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricDecisionOutputInstanceByteArraysByDecisionInstanceIds", "deleteHistoricDecisionOutputInstanceByteArraysByDecisionInstanceIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricVariableInstanceByIds", "deleteHistoricVariableInstanceByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricVariableInstanceByteArraysByIds", "deleteHistoricVariableInstanceByteArraysByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteCommentsByIds", "deleteCommentsByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteAttachmentByteArraysByIds", "deleteAttachmentByteArraysByIds_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteAttachmentByIds", "deleteAttachmentByIds_mysql");

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "deleteHistoricIncidentsByBatchIds", "deleteHistoricIncidentsByBatchIds_mysql");

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateUserOperationLogByRootProcessInstanceId", "updateUserOperationLogByRootProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateExternalTaskLogByRootProcessInstanceId", "updateExternalTaskLogByRootProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateHistoricIncidentsByRootProcessInstanceId", "updateHistoricIncidentsByRootProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateHistoricIncidentsByBatchId", "updateHistoricIncidentsByBatchId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateIdentityLinkLogByRootProcessInstanceId", "updateIdentityLinkLogByRootProcessInstanceId_mysql");

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateUserOperationLogByProcessInstanceId", "updateUserOperationLogByProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateExternalTaskLogByProcessInstanceId", "updateExternalTaskLogByProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateHistoricIncidentsByProcessInstanceId", "updateHistoricIncidentsByProcessInstanceId_mysql");
                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateIdentityLinkLogByProcessInstanceId", "updateIdentityLinkLogByProcessInstanceId_mysql");

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateOperationLogAnnotationByOperationId", "updateOperationLogAnnotationByOperationId_mysql");

                self::addDatabaseSpecificStatement($mysqlLikeDatabase, "updateByteArraysByBatchId", "updateByteArraysByBatchId_mysql");

                $constants = [];
                $constants["constant.event"] = "'event'";
                $constants["constant.op_message"] = "CONCAT(NEW_VALUE_, '_|_', PROPERTY_)";
                $constants["constant_for_update"] = "for update";
                $constants["constant.datepart.quarter"] = "QUARTER";
                $constants["constant.datepart.month"] = "MONTH";
                $constants["constant.datepart.minute"] = "MINUTE";
                $constants["constant.null.startTime"] = "null START_TIME_";
                $constants["constant.varchar.cast"] = '\'${key}\'';
                $constants["constant.integer.cast"] = "NULL";
                $constants["constant.null.reporter"] = "NULL AS REPORTER_";
                self::$dbSpecificConstants[$mysqlLikeDatabase] = $constants;
            }
        }
    }

    public function getSessionType(): ?string
    {
        return DbSqlSession::class;
    }

    public function openSession(Connection $connection = null, ?string $catalog = null, ?string $schema = null): SessionInterface
    {
        return $this->batchProcessing ?
            new BatchDbSqlSession($this, $connection, $catalog, $schema) :
            new SimpleDbSqlSession($this, $connection, $catalog, $schema);
    }

    // insert, update and delete statements /////////////////////////////////////

    public function getInsertStatement(DbEntityInterface $object): ?string
    {
        return $this->getStatement(get_class($object), $this->insertStatements, "insert");
    }

    public function getUpdateStatement(DbEntityInterface $object): ?string
    {
        return $this->getStatement(get_class($object), $this->updateStatements, "update");
    }

    public function getDeleteStatement(?string $persistentObjectClass): ?string
    {
        return $this->getStatement($persistentObjectClass, $this->deleteStatements, "delete");
    }

    public function getSelectStatement(?string $persistentObjectClass): ?string
    {
        return $this->getStatement($persistentObjectClass, $this->selectStatements, "select");
    }

    private function getStatement(?string $persistentObjectClass, array &$cachedStatements, ?string $prefix): ?string
    {
        if (array_key_exists($persistentObjectClass, $cachedStatements)) {
            $statement = $cachedStatements[$persistentObjectClass];
            return $statement;
        }
        $statement = $prefix . ClassNameUtil::getClassNameWithoutPackage($persistentObjectClass);
        $statement = substr($statement, 0, strlen($statement) - 6); // "Entity".length() = 6
        $cachedStatements[$persistentObjectClass] = $statement;
        return $statement;
    }

    // db specific mappings /////////////////////////////////////////////////////

    protected static function addDatabaseSpecificStatement(?string $databaseType, ?string $activitiStatement, ?string $doctrineStatement): void
    {
        $specificStatements = array_key_exists($databaseType, self::$databaseSpecificStatements) ? self::$databaseSpecificStatements[$databaseType] : null;
        if ($specificStatements === null) {
            $specificStatements = [];
            self::$databaseSpecificStatements[$databaseType] = [ $doctrineStatement ];
        } else {
            self::$databaseSpecificStatements[$activitiStatement][] = $doctrineStatement;
        }
    }

    public function mapStatement(?string $statement): ?string
    {
        if (empty($this->statementMappings)) {
            return $statement;
        }
        $mappedStatement = array_key_exists($statement, $this->statementMappings) ? $this->statementMappings[$statement] : null;
        return $mappedStatement ?? $statement;
    }

    // customized getters and setters ///////////////////////////////////////////

    public function setDatabaseType(?string $databaseType): void
    {
        $this->databaseType = $databaseType;
        $this->statementMappings = array_key_exists($databaseType, self::$databaseSpecificStatements) ? self::$databaseSpecificStatements[$databaseType] : [];
    }

    // getters and setters //////////////////////////////////////////////////////

    public function getSqlSessionFactory(): SqlSessionFactoryInterface
    {
        return $this->sqlSessionFactory;
    }

    public function setSqlSessionFactory(SqlSessionFactoryInterface $sqlSessionFactory): void
    {
        $this->sqlSessionFactory = $sqlSessionFactory;
    }

    public function getIdGenerator(): IdGeneratorInterface
    {
        return $this->idGenerator;
    }

    public function setIdGenerator(IdGeneratorInterface $idGenerator): void
    {
        $this->idGenerator = $idGenerator;
    }

    public function getDatabaseType(): ?string
    {
        return $this->databaseType;
    }

    public function getStatementMappings(): array
    {
        return $this->statementMappings;
    }

    public function setStatementMappings(array $statementMappings): void
    {
        $this->statementMappings = $statementMappings;
    }

    public function getInsertStatements(): array
    {
        return $this->insertStatements;
    }

    public function setInsertStatements(array $insertStatements): void
    {
        $this->insertStatements = $insertStatements;
    }

    public function getUpdateStatements(): array
    {
        return $this->updateStatements;
    }

    public function setUpdateStatements(array $updateStatements): void
    {
        $this->updateStatements = $updateStatements;
    }

    public function getDeleteStatements(): array
    {
        return $this->deleteStatements;
    }

    public function setDeleteStatements(array $deleteStatements): void
    {
        $this->deleteStatements = $deleteStatements;
    }

    public function getSelectStatements(): array
    {
        return $this->selectStatements;
    }

    public function setSelectStatements(array $selectStatements): void
    {
        $this->selectStatements = $selectStatements;
    }

    public function isDbIdentityUsed(): bool
    {
        return $this->isDbIdentityUsed;
    }

    public function setDbIdentityUsed(bool $isDbIdentityUsed): void
    {
        $this->isDbIdentityUsed = $isDbIdentityUsed;
    }

    public function isDbHistoryUsed(): bool
    {
        return $this->isDbHistoryUsed;
    }

    public function setDbHistoryUsed(bool $isDbHistoryUsed): void
    {
        $this->isDbHistoryUsed = $isDbHistoryUsed;
    }

    /*public boolean isCmmnEnabled() {
        return cmmnEnabled;
    }

    public void setCmmnEnabled(boolean cmmnEnabled) {
        $this->cmmnEnabled = cmmnEnabled;
    }

    public boolean isDmnEnabled() {
        return dmnEnabled;
    }

    public void setDmnEnabled(boolean dmnEnabled) {
        $this->dmnEnabled = dmnEnabled;
    }*/

    public function setDatabaseTablePrefix(?string $databaseTablePrefix): void
    {
        $this->databaseTablePrefix = $databaseTablePrefix;
    }

    public function getDatabaseTablePrefix(): ?string
    {
        return $this->databaseTablePrefix;
    }

    public function getDatabaseSchema(): ?string
    {
        return $this->databaseSchema;
    }

    public function setDatabaseSchema(?string $databaseSchema): void
    {
        $this->databaseSchema = $databaseSchema;
    }
}
