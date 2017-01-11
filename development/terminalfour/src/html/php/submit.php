<?php

/* TERMINALFOUR PHP Form
 *
 * Author: Valentina Del Torre <valentina.deltorre@terminalfour.com>
 */

error_reporting(E_ALL ^E_WARNING);
ini_set('display_errors', 1);

// Configuration
$admin_email = 'valentina.deltorre@gmail.com';
$email_from = 'valentina.deltorre@gmail.com';
$path = dirname(__FILE__)."/videos/";
$urlpath = "http://localhost/epa/www"."/videos/";
$csvfile = 'log.csv';
$fieldSuffix = "";
$recaptchaSecret = "6LdMpA8UAAAAAJH4iG3LOwasYk0i2lLXaClNWMf6";
$redirect_url = 'rules.html'; //Change with a T4 Tag

class FileSystem
{   
    private $path; 
    private $csv;

    public function __construct($path,$csv){

        $this->path = $path;
        $this->csv = $this->path.$csv;

        if (!is_dir($this->path)) {
            throw new Exception('Directory doesn\'t exist ',1000);
        }

    }
    
    public function writeCSV($data,$type = "Before") {
        $fp = fopen($this->csv, 'a');
        $data = array_merge(array($type),$data);
        fputcsv($fp, $data);

        fclose($fp);
    }

    public function uploadVideo($file,$date) {
        $tmp_name = $file["tmp_name"];
        $name = $this->getSlugify($date."-".basename($file["name"]));
        $result = move_uploaded_file($tmp_name, $this->path.$name);

        if (!$result) {
            throw new Exception('There were problem when I tried to upload ',1000);
        }

        return $name;
    }

    public function getSlugify($text)
    {
      // replace non letter or digits by -
      $text = preg_replace('~[^.\pL\d]+~u', '-', $text);

      // remove unwanted characters
      $text = preg_replace('~[^-.\w]+~', '', $text);

      // trim
      $text = trim($text, '-');

      // remove duplicate -
      $text = preg_replace('~-+~', '-', $text);

      // lowercase
      $text = strtolower($text);

      if (empty($text)) {
        return 'n-a';
      }

      return $text;
    }


    public function getIP() {
      return $_SERVER['REMOTE_ADDR'];
    }

    public function getDate() {

      $date = new DateTime("now", new DateTimeZone( 'UTC' ));
      return $date->format("D, d M Y H:i:s");
    }

}

class Field
{   
    private $name;
    private $label;
    private $value = null; 
    private $default; 
    private $secret; 
    private $suffix; 
    private $validation; 
    private $required = "false"; 
    private $type = "text"; 
    private $validated = false; 
    private $maxLength = 0;


    protected $error = false; 
    protected $errorMessage; 

    public function setMember($name, $value)
    {
        $this->$name = $value;

        $this->setValue();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue()
    {
      if($this->type === "file"){
        $this->value = isset($_FILES[$this->suffix.$this->name]) ? $_FILES[$this->suffix.$this->name] : null ;
      } else {
        $this->value = isset($_POST[$this->suffix.$this->name]) ? $_POST[$this->suffix.$this->name] : null ;
     }
    }

    public function printField(){
      
      if( $this->required !== false) {
        $requiredText = ' *';
      } else {
        $requiredText = '';
      }

      if ($this->validateField() === true) {
        $labelText = $this->label.$requiredText;
        $placeholderText = $this->errorMessage;
        $errorClass = "  has-error";
        $value = "";
      } else {
        $labelText = $this->label.$requiredText;
        $placeholderText = $this->placeholder;
        $errorClass = "";
        $value = $this->getValue();
      }

      if (intval($this->maxLength) > 0 ){
        $maxLength = 'maxlength="'.intval($this->maxLength).'"';
      } else {
        $maxLength = '';
      }

      switch ($this->type) {
        case "select":
          if (empty($this->value)) {
              $selected = " selected";
            } else {
              $selected = "";
            }
          echo '
              <div class="form-group'.$errorClass.'">
                  <select class="form-control" name="'.$this->suffix.$this->name.'" id="'.$this->suffix.$this->name.'" aria-label="'.$this->label.'">
                    <option '.$selected.' value="">'.$this->label.'</option>';

          foreach ($this->default as $option) {
            if (trim($option['VALUE']) == $this->value) {
              $selected = " selected";
            } else {
              $selected = "";
            }
            echo '
                    <option '.$selected.' value="'.trim($option['VALUE']).'">'.$option['LABEL'].'</option>';
          }
          echo '      
                  </select>
              </div>';
          break;
        case 'hidden':
          echo '
                  <input class="form-control" name="'.$this->suffix.$this->name.'" type="hidden" value="'.$value.'" id="'.$this->suffix.$this->name.'" aria-label="'.$this->label.'" />';
          break;
        case 'textarea':
          echo '
              <div class="form-group'.$errorClass.'">
                  <textarea class="form-control" name="'.$this->suffix.$this->name.'" id="'.$this->suffix.$this->name.'" placeholder="'.$placeholderText.'" aria-label="'.$this->label.'" '.$maxLength.' >'.$value.'</textarea>
              </div>';
          break;
      case 'submit': 
        echo '
              <div class="form-group">
                  <button type="submit" name="'.$this->suffix.$this->name.'" id="'.$this->suffix.$this->name.'" class="scu-btn scu-btn-yellow btn-large btn-wide-width">'.$placeholderText.'</button>
              </div>';
        break;
        default:
          echo '
              <div class="form-group'.$errorClass.'">
                  <input class="form-control" name="'.$this->suffix.$this->name.'" type="text" value="'.$value.'" id="'.$this->suffix.$this->name.'" placeholder="'.$placeholderText.'" aria-label="'.$this->label.'" '.$maxLength.' />
              </div>';
      }
        
    }

    public function validateField(){

      $this->error = false;
      $this->errorMessage = null;

      if (intval($this->maxLength) > 0 && mb_strlen($this->value) > (intval($this->maxLength)) && $this->value !== null){
        $this->error = true;
        $this->errorMessage = $this->label." must be lenght ".$this->maxLength." characters.";
      }

      // if (!empty($this->default) && !empty($this->value)){
      //   $found = false;
      //   foreach ($this->default as $option) {
      //     if (trim($option['VALUE']) == $this->value) {
      //       $found = true;
      //     }
      // }

      // if(!$found) {
      //   $this->error = true;
      //   $this->errorMessage = $this->label." must be one of the value inside the list.";
      // }
      // }
      
      if (strip_tags($this->value) !== $this->value && $this->value !== null && !in_array($this->type, array('file'))) {
        $this->error = true;
        $this->errorMessage = $this->label." must be a plain text";
      }

      if ($this->type === "textarea" && $this->value !== null) {
        $this->value = nl2br(addslashes($this->value));
      }

      if ($this->validation === "name" && preg_match("/^([a-z\s]+)$/i", $this->value) === 0 && $this->value !== null) {
        $this->error = true;
        $this->errorMessage = $this->label." must only contain letters and whitespace";
      } 

      if ($this->validation === "email" && !filter_var($this->value, FILTER_VALIDATE_EMAIL) && $this->value !== null) {
        $this->error = true;
        $this->errorMessage = $this->label." must contain a valid email address";
        //conver the value to lower case
        $this->value = mb_strtolower($this->value); 
      }  

      if ($this->validation === "number" && preg_match("/^([0-9]+)$/i", $this->value) === 0 && $this->value !== null) {
        $this->error = true;
        $this->errorMessage = $this->label." must contain numeric values";
      }

      if ($this->validation === "file" && $this->value !== null) {
        $file = $this->value;
        if(isset($file['name'])){
          $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
          if( !in_array($ext, $this->default)) {
            $this->error = true;
            $this->errorMessage = $this->label." is not a valid file";
          }
        } else {
          $this->error = true;
          $this->errorMessage = $this->label." is not saved correctly";
        }
      }

      if ($this->validation === "recaptcha") {

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $this->secret,
            'response' => $this->value
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);

        if($response->success == false) {
          $this->error = true;
          $this->errorMessage = "Captcha must be valid";
        } 
      }

      if($this->required !== false && $this->error === false) {
        //validate 
        if (empty($this->value) && $this->value !== null) {
          $this->error = true;
          $this->errorMessage = $this->label." is required";
        }
      }
      

      if ($this->value !== null) {
        $this->validated = !$this->error;
      } else {
        $this->validated = false;
      }
      if ($this->error === true) {
        $this->value = addslashes(strip_tags($this->value));
      }
      return $this->error;
    }

    public function isValid() {

      $this->validateField();
      return $this->validated;
    }
    
}

class Mail
{
  private $to;
  private $from;
  private $subject = "SCU - MS Dynamics Database Error"; 
  private $message = "There is a issue with database used to save form information"; 

  public function setMember($name, $value)
    {
        $this->$name = $value;
    }
    public function send(){
      if (!empty($this->to)) {
        if(empty($this->from)) {
          $this->from = $this->to;
        }
        $headers = 'To: '.$this->to . "\r\n" .
        'From: '.$this->from . "\r\n" .
        'Reply-To: '.$this->from . "\r\n" .
        'MIME-VERSION: 1.0'. "\r\n" .
        'Content-Transfer-Encoding: 8bit'. "\r\n" ;
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";



      return mail($this->to , $this->subject , $this->message, $headers);

      }
    
    }
}

try {

  $filesystem = new FileSystem($path,$csvfile);

  $Leadname = new Field();
  $Leadname->setMember("name","leadname");
  $Leadname->setMember("label","Name of applicant or Lead name if it’s team");
  $Leadname->setMember("placeholder","Enter Name");
  $Leadname->setMember("required",true);
  $Leadname->setMember("validation","name");
  $Leadname->setMember("suffix",$fieldSuffix);
  $Leadname->setMember("maxLength","50");

  $Teachersname = new Field();
  $Teachersname->setMember("name","teachersname");
  $Teachersname->setMember("label","Enter Teachers name");
  $Teachersname->setMember("required",true);
  $Teachersname->setMember("validation","name");
  $Teachersname->setMember("suffix",$fieldSuffix);
  $Teachersname->setMember("maxLength","50");

  $Schoolname = new Field();
  $Schoolname->setMember("name","schoolname");
  $Schoolname->setMember("label","Enter Teachers name");
  $Schoolname->setMember("required",true);
  $Schoolname->setMember("validation","name");
  $Schoolname->setMember("suffix",$fieldSuffix);
  $Schoolname->setMember("maxLength","50");

  $County = new Field();
  $County->setMember("name","county");
  $County->setMember("label","Enter your County");
  $County->setMember("required",true);
  $County->setMember("validation","name");
  $County->setMember("suffix",$fieldSuffix);
  $County->setMember("maxLength","50");

  $Email = new Field();
  $Email->setMember("name","email");
  $Email->setMember("label","Enter Email");
  $Email->setMember("required",true);
  $Email->setMember("validation","email");
  $Email->setMember("suffix",$fieldSuffix);
  $Email->setMember("maxLength","50");

  $Video = new Field();
  $Video->setMember("name","video");
  $Video->setMember("label","Enter Video");
  $Video->setMember("required",true);
  $Video->setMember("suffix",$fieldSuffix);
  $Video->setMember("validation","file");
  $Video->setMember("type","file");
  $Video->setMember("default",array("jpg","png","mp4","mp3","gif"));

  $Captcha = new Field();
  $Captcha->setMember("name","g-recaptcha-response");
  $Captcha->setMember("label","Captcha");
  $Captcha->setMember("validation","recaptcha");
  $Captcha->setMember("required",true);
  $Captcha->setMember("secret",$recaptchaSecret);
  $Captcha->setMember("type","hidden");


  // && $Captcha->isValid()
  if ($Leadname->isValid() && $Teachersname->isValid() && $Schoolname->isValid() && $County->isValid() && $Email->isValid() && $Video->isValid()) {

    $data = array();
    $data["Date"] = $filesystem->getDate();
    $data["IP"] = $filesystem->getIP();
    $data["Leadname"] = $Leadname->getValue();
    $data["Teachersname"] = $Teachersname->getValue();
    $data["Schoolname"] = $Schoolname->getValue();
    $data["County"] = $County->getValue();
    $data["Email"] = $Email->getValue();

    $filesystem->writeCSV($data, "Start");

    $data["Video"] = $filesystem->uploadVideo($Video->getValue(),$data["Date"]);

    $filesystem->writeCSV($data, "After Upload");

    $message = "
    <h2>Form Information</h2>
    <strong>Name of applicant or Lead name if it's team</strong>: ".$data['Leadname']."<br />
    <strong>Teachers name</strong>: ".$data['Teachersname']."<br />
    <strong>School name</strong>: ".$data['Schoolname']."<br />
    <strong>County</strong>: ".$data['County']."<br />
    <strong>Email</strong>: ".$data['Email']."<br />
    <h2>Video</h2>
    <strong>Link</strong>: <a href=\"$urlpath".$data['Video']."\">$urlpath".$data['Video']."</a><br />
    <h2>System information</h2>
    <strong>Date</strong>: ".$data['Date']."<br />
    <strong>IP Address</strong>: ".$data['IP']."<br />
    <strong>CSV Log</strong>: <a href=\"$urlpath$csvfile\">$urlpath$csvfile</a><br />
";

    $Mail = new Mail();
    $Mail->setMember("to",$admin_email);
    $Mail->setMember("from",$email_from);
    $Mail->setMember("subject","The Story of your Stuff: New Upload");
    $Mail->setMember("message",$message);
    $data["Email Answer"] = $Mail->send();

    $filesystem->writeCSV($data, "End");

    if ($data["Email Answer"]){
      header("Location: ".$redirect_url);
      die();
    }

  }
  $errorMessage = null;
} catch (Exception $e) {
  if ($e->getCode() === 1000) {
  $Mail = new Mail();
  $Mail->setMember("to",$admin_email);
  $Mail->setMember("from",$email_from);
  $Mail->setMember("subject","The Story of your Stuff: Error");
  $Mail->setMember("message","The form has the following error:".$e->getMessage());
  $Mail->send();
  } 
  $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   @@include('head.html', {"pageTitle": "Submit"})
  <script src="style-assets/js/jquery.validation.js"></script>
  <script src="style-assets/js/additional-methods.js"></script>
  <script src='https://www.google.com/recaptcha/api.js'></script>
<script>

$(function() {

  $("form[name='submitForm']").validate({
    rules: {
   
      leadname: { 
        lettersonly: true, 
        required: true
      },
      teachersname:{ 
        lettersonly: true, 
        required: true
      },
      email: {
        required: true,

        email: true
      },
      county: { 
        lettersonly: true, 
        required: true
      },
      schoolname: { 
        lettersonly: true, 
        required: true
      },
      video:"required"
    },

    messages: {
      leadname: {
            required: "Please enter your lead name",
            lettersonly: "Can only be letters"
          },
      teachersname: {
            required: "Please enter your teachers name",
            lettersonly: "Can only be letters"
          },
      schoolname: {
            required: "Please enter your school name",
            lettersonly: "Can only be letters"
          },
      county: {
            required: "Please enter your county name",
            lettersonly: "Can only be letters"
          },
      email: {
            required: "Please enter your lead name",
            email: "Can only be a valid email address"
          },
      video:"please select a video to upload"
    },

    submitHandler: function(form) {
      form.submit();
    }
  });
});
</script>
</head>
<body>
  <div class="container">
    @@include('nav-inner.html')

<main class="main-content">   
  <article class="row marketing bg-1">
    <div class="col-xs-12 dashed">
      <div class="row">
        <div class="col-xs-12">
          <h2>READY TO SUBMIT? LET’S DO THIS!</h2>
        </div>
        <div class="image-container">
         <!--  <img alt="step one step two and step three" id="replaceSteps" src="style-assets/images/replace-steps.png"> -->
          <div class="col-lg-4 image-containers step-images"">
          <a href="#first"><img alt="first prize" src="style-assets/images/home/step1big.png"></a></div>
          <div class="col-lg-4 image-container2">
          <a href="#second"><img alt="second prize" src="style-assets/images/home/step2big.png"></a></div>
          <div class="col-lg-4 image-container3">
          <a href="#third"><img alt="third prize" src="style-assets/images/home/step3big.png"></a></div>
        </div>
      </div>
    </div>
  </article>

    
  
    <article class="row marketing bg-form">
    <div class="col-xs-12 dashed">
      <div class="row">
        <div class="col-xs-12">

          <?php if ($errorMessage) : ?>
          <div class="errror">
            <p><?php echo $errorMessage; ?></p>
          </div>
          <?php endif; ?>

           <form action="thankyou.html" id="submitForm" method="get" name="submitForm">
           <span class="pull-right topNotice"><span class="reqred">* &nbsp;</span>&nbsp;Required fields</span>
             <div class="col-xs-12 dashed">
             <a name="first"></a>
              <div class="col-lg-3"><img alt="first prize" class="formIcon" src="style-assets/images/home/step1big.png"></div>
              <div class="col-lg-9">
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label for="leadname">Name of applicant or Lead name if it’s team:</label> <input class="form-control" id="leadname" name="leadname" placeholder="Enter name" type="text"  value="<?php echo isset($Leadname) ? $Leadname->getValue() : null; ?>">
                </div>
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label for="teachersname">Teachers name:</label> <input class="form-control" id="teachersname" name="teachersname" placeholder="Enter Teachers name" type="text"  value="<?php echo isset($Teachersname) ? $Teachersname->getValue() : null; ?>">
                </div>
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label for="schoolname">School name:</label> <input class="form-control" id="schoolname" name="schoolname" placeholder="Enter schools name" type="text" value="<?php echo isset($Schoolname) ? $Schoolname->getValue() : null; ?>">
                </div>
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label for="county">County:</label> <input class="form-control" id="county" name="county" placeholder="Enter your county" type="text" value="<?php echo isset($County) ? $County->getValue() : null; ?>">
                </div>
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label for="email">E-mail Address:</label> <input class="form-control" id="email" name="email" placeholder="Enter your email" type="email" value="<?php echo isset($Email) ? $Email->getValue() : null; ?>">
                </div>
              </div>
              <div class="row dashed spacer"></div>
              
              <div class="col-lg-3"><img alt="second prize" class="formIcon1" src="style-assets/images/home/step2big.png"></div>
              <div class="col-lg-9">
              <a name="second"></a>
                <div class="form-group">
                  <span class="reqred">*</span>&nbsp; <label>Upload Video</label> <input accept="image/*" class="upload" name="video" type="file">
                </div>
                <div class="g-recaptcha" data-sitekey="6LdMpA8UAAAAAB8GjnAxusQMybIj7mxwP92Gt8yM"></div>
              </div>
            </div>
           
             <div class="col-xs-12 borderBox">
              <div class="col-lg-3"><img alt="third prize" class="formIcon1" src="style-assets/images/home/step3big.png"></div>
              <div class="col-lg-9">
              <a name="third"></a>
                <button class="btn submitButton" type="submit">SEND!</button>
              </div>
            </div>
          </form>

         </div>
        </div>
       </div>
    </article>
 </main>
  @@include('footer.html')

</body>
</html>