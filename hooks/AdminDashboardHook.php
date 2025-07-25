<?php
class AdminDashboardHook
{
    private $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    public function displayAdminDashboard()
    {
        $quotes_count = count(Quote::getAllQuotes());
        
        return '
        <div class="col-lg-6">
            <section class="dash_trends panel">
                <header class="panel-heading">
                    <i class="icon-file-text"></i> Devis
                </header>
                <div class="panel-body">
                    <p class="dash-trends-number">' . $quotes_count . '</p>
                    <p class="dash-trends-label">Devis en cours</p>
                    <p class="dash-trends-cta">
                        <a href="' . Context::getContext()->link->getAdminLink('AdminQuote') . '" class="btn btn-primary">
                            <i class="icon-eye"></i> Voir tous les devis
                        </a>
                    </p>
                </div>
            </section>
        </div>';
    }
}
