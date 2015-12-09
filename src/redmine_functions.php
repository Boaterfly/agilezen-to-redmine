<?php

namespace AgileZenToRedmine\Redmine;

use AgileZenToRedmine\Api\AgileZen\Attachment;
use AgileZenToRedmine\Api\AgileZen\Comment;
use AgileZenToRedmine\Api\AgileZen\Project;
use AgileZenToRedmine\Api\AgileZen\Story;
use AgileZenToRedmine\Api\AgileZen\User;

/**
 * Sanitize a string for use as a Redmine project identifier.
 *
 * @return string $str
 * @return string
 */
function identifier_from_agilezen_project(Project $project)
{
    return sanitize_identifier($project->name);
}

/**
 * @param string $str
 * @return string
 */
function sanitize_identifier($str)
{
    if (!ctype_alpha((string) $str[0])) {
        throw new \InvalidArgumentException('An identifier must start with a letter.');
    }

    return implode('', array_filter(
        str_split(strtolower($str)),
        function ($c) {
            return ctype_alnum((string) $c)
                || $c === '-'
                || $c === '_'
            ;
        }
    ));
}

/**
 * @return string
 */
function description_from_agilezen_project(Project $project)
{
    return implode("\n", [
        $project->description,
        '',
        "Project #{$project->id} from AgileZen.",
        "Originally created at {$project->createTime} by {$project->owner->name}."
    ]);
}

/**
 * @return string
 */
function login_from_agilezen_user(User $user)
{
    return explode('@', $user->email)[0];
}

/**
 * @return string
 */
function subject_from_agilezen_story(Story $story)
{
    $firstSentence = explode("\n", $story->text)[0];

    $subject = (mb_strlen($firstSentence) > 128)
        ? mb_substr($firstSentence, 0, 127) . '…'
        : $firstSentence
    ;

    if (strlen(trim($subject)) <= 0) {
        return "Blank subject for AgileZen story #{$story->id}.";
    } else {
        return $subject;
    }
}

/**
 * @return string
 */
function description_from_agilezen_story(Story $story, Project $project)
{
    $link = sprintf(
        '[#%s](https://agilezen.com/project/%s/story/%s)',
        $story->id,
        $project->id,
        $story->id
    );

    return implode("\n", [
        $story->text,
        '',
        $story->details,
        '',
        "Story $link from AgileZen, originally created at {$story->getCreateTime()}."
    ]);
}

/**
 * @return string
 */
function note_from_agilezen_comment(Comment $comment, Story $story, Project $project)
{
    $link = sprintf(
        '[#%s](https://agilezen.com/project/%s/story/%s#comments)',
        $comment->id,
        $project->id,
        $story->id
    );

    return implode("\n", [
        $comment->text,
        '',
        "Comment $link from AgileZen, originally created at {$comment->createTime}.",
    ]);
}

/**
 * @return string
 */
function description_from_agilezen_attachment(Attachment $attachment)
{
    return "Attachment #{$attachment->id} from AgileZen.";
}
