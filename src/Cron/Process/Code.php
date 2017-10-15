<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Code as CodeModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Code implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;
    use ProcessTrait;

    /** @var Container */
    protected $container;

    /**
     * @param Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->logger = new NullLogger;
    }

    public function run()
    {
        try {
            $codeEvents = $this->fetchCodeEvents($this->container->get('codeModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($codeEvents as $codeEvent) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'code',
                $codeEvent['id']
            );

            if ($event) {
                continue;
            }

            $codeEventMetadata = json_decode($codeEvent['metadata']);

            try {
                [ $description, $descriptionHtml ] = $this->getDescriptions(
                    $codeEvent['type'],
                    $codeEventMetadata
                );
            } catch (Exception $exception) {
                $this->logger->debug($exception->getMessage());
                continue;
            }

            try {
                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $this->container->get('typeModel'),
                    $this->container->get('userModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($codeEvent['datetime'])),
                    (object) [],
                    'Jacob Emerick',
                    'code',
                    $codeEvent['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added code codeEvent: {$codeEvent['id']}");
        }
    }

    /**
     * @param CodeModel $codeModel
     * @return array
     */
    protected function fetchCodeEvents(CodeModel $codeModel)
    {
        return $codeModel->getEvents();
    }

    /**
     * @param string $type
     * @param stdclass $metadata
     * @return array
     */
    protected function getDescriptions($type, stdclass $metadata)
    {
        switch ($type) {
            case 'CreateEvent':
                if (
                    $metadata->payload->ref_type == 'branch' ||
                    $metadata->payload->ref_type == 'tag'
                ) {
                    $description = $this->getCreateDescription($metadata);
                    $descriptionHtml = $this->getCreateDescriptionHtml($metadata);
                } elseif ($metadata->payload->ref_type == 'repository') {
                    $description = $this->getCreateRepositoryDescription($metadata);
                    $descriptionHtml = $this->getCreateRepositoryDescription($metadata);
                } else {
                    throw new Exception("Skipping create event: {$metadata->payload->ref_type}");
                }
                break;
            case 'ForkEvent':
                $description = $this->getForkDescription($metadata);
                $descriptionHtml = $this->getForkDescriptionHtml($metadata);
                break;
            case 'PullRequestEvent':
                $description = $this->getPullRequestDescription($metadata);
                $descriptionHtml = $this->getPullRequestDescriptionHtml($metadata);
                break;
            case 'PushEvent':
                $description = $this->getPushDescription($metadata);
                $descriptionHtml = $this->getPushDescriptionHtml($metadata);
                break;
            default:
                throw new Exception("Skipping an event type: {$type}");
                break;
        }

        return [ $description, $descriptionHtml];
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateDescription(stdclass $metadata)
    {
        return sprintf(
            'Created %s %s at %s.',
            $metadata->payload->ref_type,
            $metadata->payload->ref,
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateDescriptionHtml(stdclass $metadata)
    {
        return sprintf(
            '<p>Created %s %s at <a href="%s" target="_blank" title="Github | %s">%s</a>.</p>',
            $metadata->payload->ref_type,
            $metadata->payload->ref,
            "https://github.com/{$metadata->repo->name}",
            $metadata->repo->name,
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateRepositoryDescription(stdclass $metadata)
    {
        return sprintf(
            'Created %s %s.',
            $metadata->payload->ref_type,
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateRepositoryDescriptionHtml(stdclass $metadata)
    {
        return sprintf(
            '<p>Created %s <a href="%s" target="_blank" title="Github | %s">%s</a>.</p>',
            $metadata->payload->ref_type,
            "https://github.com/{$metadata->repo->name}",
            $metadata->repo->name,
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getForkDescription(stdclass $metadata)
    {
        return sprintf(
            'Forked %s to %s',
            $metadata->repo->name,
            $metadata->payload->forkee->full_name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getForkDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            '<p>Forked <a href="%s" target="_blank" title="Github | %s">%s</a> ',
            "https://github.com/{$metadata->repo->name}",
            $metadata->repo->name,
            $metadata->repo->name
        );
        $description .= sprintf(
            'to <a href="%s" target="_blank" title="Github | %s">%s</a>.',
            $metadata->payload->forkee->html_url,
            $metadata->payload->forkee->full_name,
            $metadata->payload->forkee->full_name
        );
        return $description;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPullRequestDescription(stdclass $metadata)
    {
        return sprintf(
            '%s a pull request at %s',
            ucwords($metadata->payload->action),
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPullRequestDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            '<p>%s pull request <a href="%s" target="_blank" title="Github | %s PR %d">%d</a> ',
            ucwords($metadata->payload->action),
            $metadata->payload->pull_request->html_url,
            $metadata->repo->name,
            $metadata->payload->number,
            $metadata->payload->number
        );
        $description .= sprintf(
            'at <a href="%s" target="_blank" title="Github | %s">%s</a>.</p>',
            "https://github.com/{$metadata->repo->name}",
            $metadata->repo->name,
            $metadata->repo->name
        );
        return $description;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPushDescription(stdclass $metadata)
    {
        return sprintf(
            'Pushed some code at %s.',
            $metadata->repo->name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPushDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            "<p>Pushed some code at <a href=\"%s\" target=\"_blank\" title=\"Github | %s\">%s</a>.</p>",
            $metadata->payload->ref,
            "https://github.com/{$metadata->repo->name}",
            $metadata->repo->name,
            $metadata->repo->name
        );
        $description .= '<ul>';
        foreach ($metadata->payload->commits as $commit) {
            $commitMessage = $commit->message;
            $commitMessage = strtok($commitMessage, "\n");
            if (strlen($commitMessage) > 72) {
                $commitMessage = wordwrap($commitMessage, 65);
                $commitMessage .= '&hellip;';
            }
            $description .= sprintf(
                "<li><a href=\"%s\" target=\"_blank\" title=\"Github | %s\">%s</a> %s.</li>",
                "https://github.com/{$metadata->repo->name}/commit/{$commit->sha}",
                substr($commit->sha, 0, 7),
                substr($commit->sha, 0, 7),
                $commitMessage
            );
        }
        $description .= '</ul>';
        return $description;
    }
}
