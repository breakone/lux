<?php
declare(strict_types=1);
namespace In2code\Lux\Domain\Service;

use In2code\Lux\Domain\Model\Download;
use In2code\Lux\Domain\Model\Linkclick;
use In2code\Lux\Domain\Model\Linklistener;
use In2code\Lux\Domain\Model\Log;
use In2code\Lux\Domain\Model\Visitor;
use In2code\Lux\Domain\Repository\LogRepository;
use In2code\Lux\Domain\Repository\VisitorRepository;
use In2code\Lux\Utility\ObjectUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class LogService
 */
class LogService
{
    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logNewVisitor(Visitor $visitor)
    {
        $this->log(Log::STATUS_NEW, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logIdentifiedVisitor(Visitor $visitor)
    {
        $this->log(Log::STATUS_IDENTIFIED, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logIdentifiedVisitorFormListening(Visitor $visitor)
    {
        $this->log(Log::STATUS_IDENTIFIED_FORMLISTENING, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logIdentifiedVisitorByEmail4Link(Visitor $visitor)
    {
        $this->log(Log::STATUS_IDENTIFIED_EMAIL4LINK, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logIdentifiedVisitorByLuxletterlink(Visitor $visitor)
    {
        $this->log(Log::STATUS_IDENTIFIED_LUXLETTERLINK, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logIdentifiedVisitorByFrontendauthentication(Visitor $visitor)
    {
        $this->log(Log::STATUS_IDENTIFIED_FRONTENDAUTHENTICATION, $visitor);
    }

    /**
     * @param Visitor $visitor
     * @param string $href
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logEmail4LinkEmail(Visitor $visitor, string $href)
    {
        $this->log(Log::STATUS_IDENTIFIED_EMAIL4LINK_SENDEMAIL, $visitor, ['href' => $href]);
    }

    /**
     * @param Visitor $visitor
     * @param string $href
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logEmail4LinkEmailFailed(Visitor $visitor, string $href)
    {
        $this->log(Log::STATUS_IDENTIFIED_EMAIL4LINK_SENDEMAILFAILED, $visitor, ['href' => $href]);
    }

    /**
     * @param Download $download
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logDownload(Download $download)
    {
        $this->log(Log::STATUS_DOWNLOAD, $download->getVisitor(), ['href' => $download->getHref()]);
    }

    /**
     * @param Visitor $visitor
     * @param Linklistener $linklistener
     * @param int $pageUid
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function logLinkListener(Visitor $visitor, Linklistener $linklistener, int $pageUid)
    {
        $this->log(
            Log::STATUS_LINKLISTENER,
            $visitor,
            ['linklistener' => $linklistener->getUid(), 'pageUid' => $pageUid]
        );
    }

    /**
     * @param QueryResultInterface $visitors
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function logVisitorMergeByFingerprint(QueryResultInterface $visitors)
    {
        $visitor = $visitors->getFirst();
        $identifiers = [];
        foreach ($visitors as $visitor) {
            $identifiers[] = $visitor->getUid();
        }
        $this->log(
            Log::STATUS_MERGE_BYFINGERPRINT,
            $visitor,
            ['visitorUidsMergedIntoFirst' => implode(',', $identifiers)]
        );
    }

    /**
     * @param QueryResultInterface $visitors
     * @return void
     * @throws Exception
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function logVisitorMergeByEmail(QueryResultInterface $visitors)
    {
        $visitor = $visitors->getFirst();
        $identifiers = [];
        foreach ($visitors as $visitor) {
            $identifiers[] = $visitor->getUid();
        }
        $this->log(
            Log::STATUS_MERGE_BYEMAIL,
            $visitor,
            ['visitorUidsMergedIntoFirst' => implode(',', $identifiers)]
        );
    }

    /**
     * @param Visitor $visitor
     * @param int $shownContentUid
     * @param int $containerContentUid
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function logContextualContent(Visitor $visitor, int $shownContentUid, int $containerContentUid)
    {
        $this->log(
            Log::STATUS_CONTEXTUAL_CONTENT,
            $visitor,
            [
                'shownContentUid' => $shownContentUid,
                'containerContentUid' => $containerContentUid
            ]
        );
    }

    /**
     * @param int $status
     * @param Visitor $visitor
     * @param array $properties
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     */
    protected function log(int $status, Visitor $visitor, array $properties = [])
    {
        $logRepository = ObjectUtility::getObjectManager()->get(LogRepository::class);
        $visitorRepository = ObjectUtility::getObjectManager()->get(VisitorRepository::class);

        $log = ObjectUtility::getObjectManager()->get(Log::class)->setStatus($status)->setProperties($properties);
        $logRepository->add($log);
        $visitor->addLog($log);
        if ($visitor->getUid() > 0) {
            $visitorRepository->update($visitor);
        } else {
            $visitorRepository->add($visitor);
        }
        $logRepository->persistAll();
    }
}
