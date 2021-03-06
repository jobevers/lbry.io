<?php

/**
 * Description of MailActions
 *
 * @author jeremy
 */
class MailActions extends Actions
{
  public static function executeListSubscribe()
  {
    $nextUrl = isset($_POST['returnUrl']) && $_POST['returnUrl'] ? $_POST['returnUrl'] : '/join-list';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    {
      return Controller::redirect($nextUrl);
    }

    Session::set(Session::KEY_LIST_SUB_SIGNATURE, isset($_POST['listSig']) ? $_POST['listSig'] : true);

    $email = $_POST['email'];
    if (!$email|| !filter_var($email, FILTER_VALIDATE_EMAIL))
    {
      Session::set(Session::KEY_LIST_SUB_ERROR, $email ? __('Please provide a valid email address.') : __('Please provide an email address.'));
    }
    elseif (!$_POST['listId'])
    {
      Session::set(Session::KEY_LIST_SUB_ERROR, __('List not provided.'));
    }
    else
    {
      $mcApi = new Mailchimp();
      $mcListId = $_POST['listId'];
      $mergeFields = isset($_POST['mergeFields']) ? unserialize($_POST['mergeFields']) : [];
      $success = $mcApi->listSubscribe($mcListId, $email, $mergeFields, 'html', false);
      if ($success)
      {
        Session::set(Session::KEY_MAILCHIMP_LIST_IDS, array_merge(Session::get(Session::KEY_MAILCHIMP_LIST_IDS, []), [$mcListId]));
        Session::set(Session::KEY_LIST_SUB_SUCCESS, true);
        Session::set(Session::KEY_LIST_SUB_FB_EVENT, isset($_POST['fbEvent']) ? $_POST['fbEvent'] : null);
      }
      else
      {
        $error = $mcApi->errorMessage ?: __('Something went wrong adding you to the list.');
        Session::set(Session::KEY_LIST_SUB_ERROR, $error);
      }
    }

    return Controller::redirect($nextUrl);
  }

  public static function prepareJoinList(array $vars)
  {
    $vars['listSig'] = md5(serialize($vars));
    $vars += ['btnClass' => 'btn-primary', 'returnUrl' => $_SERVER['REQUEST_URI']];

    if (Session::get(Session::KEY_LIST_SUB_SIGNATURE) == $vars['listSig'])
    {
      $vars['error'] = Session::get(Session::KEY_LIST_SUB_ERROR);
      Session::unsetKey(Session::KEY_LIST_SUB_ERROR);

      $vars['success'] = Session::get(Session::KEY_LIST_SUB_SUCCESS) ? __('Great success! Welcome to LBRY.') : false;
      $vars['fbEvent'] = Session::get(Session::KEY_LIST_SUB_FB_EVENT) ?: 'Lead';
      Session::unsetKey(Session::KEY_LIST_SUB_SUCCESS);
      Session::unsetKey(Session::KEY_LIST_SUB_FB_EVENT);
      Session::unsetKey(Session::KEY_LIST_SUB_SIGNATURE);
    }
    else
    {
      $vars['success'] = false;
    }

    return $vars;
  }
}