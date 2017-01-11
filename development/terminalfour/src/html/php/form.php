<?php

        $leadname;
        $teachersname;
        $schoolname;
        $county;
        $email;
        $video
        $captcha;

        if(isset($_POST['leadname'])){
          $leadname=$_POST['leadname'];

        }if(isset($_POST['teachersname'])){
          $teachersname=$_POST['teachersname'];
        }
        if(isset($_POST['schoolname'])){
          $schoolname=$_POST['schoolname'];

        }if(isset($_POST['county'])){
          $county=$_POST['county'];
        }
        if(isset($_POST['email'])){
          $email=$_POST['email'];

        }if(isset($_POST['video'])){
          $video=$_POST['video'];
        }

        if(isset($_POST['g-recaptcha-response'])){
          $captcha=$_POST['g-recaptcha-response'];
        }

        if(!$captcha){
          echo '<h2>Please check the the captcha form.</h2>';
          exit;
        }




        $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6LdMpA8UAAAAAJH4iG3LOwasYk0i2lLXaClNWMf6&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
        if($response.success==false)
        {
          echo '<h2>You are spammer ! Get the @$%K out</h2>';
        }else
        {
          echo '<h2>Thanks for posting comment.</h2>';
        }
?>
