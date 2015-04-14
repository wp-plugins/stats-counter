<?php

if (!class_exists('WPAdm_Method_Widget')) {
    class WPAdm_Method_Widget extends WPAdm_Method_Class {
        // get result
        public function getResult()
        {
            WPAdm_Core::log("start widget method", __CLASS__);
            $this->result->setData('not result');
            wpadm_wp_stat::on_activate();
            if (isset($this->params['side'])) {
                $this->deleteWidget();
                $this->addWidget($this->params['side']);
            }
            $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
            return $this->result;
        }

        public function addWidget($sidebar_id = false)
        {
            $message = wpadm_wp_stat::addWidget($sidebar_id);
            if ($message) {
                $this->result->setData($message);
            }
            /*if (!$sidebar_id) {
                $sidebar_id = 'sidebar-1';
            }
            $sidebars_widgets = get_option( 'sidebars_widgets' );

            if (isset($sidebars_widgets[$sidebar_id])) {
                $id = count( $sidebars_widgets[$sidebar_id] ); 
                $widget_ = $sidebars_widgets[$sidebar_id][$id-1];
                $id_widget = explode("-", $widget_ );
                if (!isset($id_widget[1])) {
                    $id_widget[1] = 1;
                }
                if (!in_array("wpadm_counter_widget-" . $id_widget[1], $sidebars_widgets[$sidebar_id])) {
                    $sidebars_widgets[$sidebar_id] = array_merge($sidebars_widgets[$sidebar_id] , array( "wpadm_counter_widget-" . $id_widget[1] ));
                    $ops = get_option( 'widget_wpadm_counter_widget' );
                    $ops[$id_widget[1]] = array(
                    'title' => 'WPADM Counter',
                    );
                    update_option( 'widget_wpadm_counter_widget', $ops ); 
                    update_option( 'sidebars_widgets', $sidebars_widgets );
                    $this->result->setData('add widget to ' . $sidebar_id);
                   
                } else {
                    $this->result->setData('In this sidebar(' . $sidebar_id . ') widget exist' );
                    
                } 
            }
            $this->result->setData('This is sidebar(' . $sidebar_id . ') not exist'); 
            return true;   */
        }
        public function deleteWidget()
        {
              return wpadm_wp_stat::deleteWidget();
        }
    }
}