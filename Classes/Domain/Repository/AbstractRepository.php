<?php
declare(strict_types=1);
namespace In2code\Lux\Domain\Repository;

use In2code\Lux\Domain\Model\Transfer\FilterDto;
use In2code\Lux\Utility\ObjectUtility;
use In2code\Lux\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING
    ];

    /**
     * @return void
     */
    public function initializeObject()
    {
        /** @var Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function persistAll()
    {
        $persistanceManager = ObjectUtility::getObjectManager()->get(PersistenceManager::class);
        $persistanceManager->persistAll();
    }

    /**
     * @param array $identifiers
     * @param string $tableName
     * @return array must be array - otherwise pagebrowser seems to be broken (for whatever reason)
     */
    protected function convertIdentifiersToObjects(array $identifiers, string $tableName): array
    {
        $identifierList = implode(',', $identifiers);
        $sql = 'select * from ' . $tableName . ' where uid in (' . $identifierList . ')'
            . 'ORDER BY FIELD(uid, ' . $identifierList . ')';
        $query = $this->createQuery();
        $query = $query->statement($sql);
        return $query->execute()->toArray();
    }

    /**
     * @param FilterDto $filter
     * @param string $table
     * @return string
     */
    protected function extendWhereClauseWithFilterSearchterms(FilterDto $filter, string $table = ''): string
    {
        $sql = '';
        if ($filter->getSearchterms() !== []) {
            foreach ($filter->getSearchterms() as $searchterm) {
                if ($sql === '') {
                    $sql .= ' and (';
                } else {
                    $sql .= ' or ';
                }

                if (MathUtility::canBeInterpretedAsInteger($searchterm)) {
                    $sql .= ($table !== '' ? $table . '.' : '') . 'uid = ' . (int)$searchterm;
                } else {
                    $sql .= ($table !== '' ? $table . '.' : '') .  ' title like "%'
                        . StringUtility::cleanString($searchterm) . '%"';
                }
            }
            $sql .= ')';
        }
        return $sql;
    }

    /**
     * Returns part of a where clause like
     *      " and crdate > 1223455 and crdate < 987876"
     *
     * @param FilterDto $filter
     * @param bool $andPrefix
     * @param string $table table with crdate (normally the main table)
     * @return string
     * @throws \Exception
     */
    protected function extendWhereClauseWithFilterTime(
        FilterDto $filter,
        bool $andPrefix = true,
        string $table = ''
    ): string {
        $field = 'crdate';
        if ($table !== '') {
            $field = $table . '.' . $field;
        }
        $string = '';
        if ($andPrefix === true) {
            $string .= ' and ';
        }
        $string .= $field . '>' . $filter->getStartTimeForFilter()->format('U')
            . ' and ' . $field . '<' . $filter->getEndTimeForFilter()->format('U');
        return $string;
    }

    /**
     * Returns part of a where clause like
     *      " and v.scoring >= 90"
     *
     * @param FilterDto $filter
     * @param string $table table name of the visitor table
     * @return string
     */
    protected function extendWhereClauseWithFilterScoring(FilterDto $filter, string $table = ''): string
    {
        $sql = '';
        if ($filter->getScoring() > 0) {
            $field = 'scoring';
            if ($table !== '') {
                $field = $table . '.' . $field;
            }
            $sql .= ' and ' . $field . ' >= ' . $filter->getScoring();
        }
        return $sql;
    }

    /**
     * Returns part of a where clause like
     *      " and cs.category = 4"
     *
     * @param FilterDto $filter
     * @param string $table table name of the categoryscoring table
     * @return string
     */
    protected function extendWhereClauseWithFilterCategoryScoring(FilterDto $filter, string $table = ''): string
    {
        $sql = '';
        if ($filter->getCategoryScoring() !== null) {
            $field = 'category';
            if ($table !== '') {
                $field = $table . '.' . $field;
            }
            $sql .= ' and ' . $field . ' = ' . $filter->getCategoryScoring()->getUid();
        }
        return $sql;
    }
}
