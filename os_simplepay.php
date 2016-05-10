<?php
    
class os_simplepay extends os_payment{
    
    var $_mode = 0;
    var $_url = 'https://checkout.simplepay.ng/v1/payments/form/';
    var $_params = array();
    var $_data = array();

    function os_simplepay($params) {

        parent::setName('os_simplepay');
        parent::os_payment();

        parent::setCreditCard(false);
        parent::setCardType(false);
        parent::setCardCvv(false);
        parent::setCardHolderName(false);

        $this->ipn_log = true ;
        $this->ipn_log_file = JPATH_COMPONENT.'/ipn_logs.txt';
        $this->_mode = $params->get('simplepay_mode') ;
        
        $this->setParam('key',$params->get('simplepay_public_test_api_key'));
        
        if ($this->_mode){
            $this->setParam('key',$params->get('simplepay_public_live_api_key'));
        }

        $this->setParam('payment_type','checkout');
        $this->setParam('currency','NGN');

    }

    function processPayment($row, $data) {

        $siteUrl = JURI::base() ;
        $Itemid = JRequest::getInt('Itemid');

        $this->setParam('payment_id',$row->id);

        $this->setParam('email',$row->email);
        $this->setParam('phone',$row->phone);

        $this->setParam('success_url', $siteUrl.'index.php?option=com_osmembership&task=payment_confirm&payment_method=os_simplepay');
        $this->setParam('cancel_url', $siteUrl.'index.php?option=com_osmembership&view=cancel&id='.$row->id.'&Itemid='.$Itemid);
        $this->setParam('fail_url', $siteUrl.'index.php?option=com_osmembership&task=payment_confirm&payment_method=os_simplepay');

        $this->setParam('description', $data['item_name']);
        $this->setParam('amount', $data['amount'].'00');

        $this->submitPost();

    }

    function submitPost() {
        ?>
        <div class="contentheading"><?php echo "Redirecting to SimplePay Gateway"; ?></div>
        <form method="post" action="<?php echo $this->_url; ?>" name="osm_form" id="osm_form">
            <?php
            foreach ($this->_params as $key=>$val) {
                echo '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
                echo "\n";
            }
            ?>
            <script type="text/javascript">
                function redirect() {
                    document.osm_form.submit();
                }

                jQuery(document).ready(function(){
                    redirect();
                });

            </script>
        </form>
        <?php
    }

    function verifyPayment() {

        $row = JTable::getInstance('OsMembership', 'Subscriber');
        $Itemid = JRequest::getInt('Itemid');

        $row->load($_POST['payment_id']);
        
        $row->transaction_id = $_POST['token'];
        $row->payment_date = date('Y-m-d H:i:s');
        $row->published = 1 ;
        $row->store();

        JPluginHelper::importPlugin( 'osmembership' );
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger( 'onMembershipActive', array($row));

        $mainframe = JFactory::getApplication();
        $mainframe->redirect(JRoute::_('index.php?option=com_osmembership&view=complete&act='.$row->act.'&subscription_code='.$row->subscription_code.'&Itemid='.$Itemid, false, false));

        return true;
    }

    function setParam($name, $val) {
        $this->_params[$name] = $val;
    }

    function setParams($params) {
        foreach ($params as $key => $value) {
            $this->_params[$key] = $value ;
        }
    }




}
