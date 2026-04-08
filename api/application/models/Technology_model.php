<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Technology_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * Get
     *
     * @param boolean $id
     * @param array $where
     * @return void
     */
    public function get($where = array())
    {

        $columns = [
            db_prefix() .'technology.name',
            db_prefix() .'technology.description',
            db_prefix() .'technology.long_description',
            db_prefix() .'technology.folder',
            db_prefix() .'technology.staffid',
            db_prefix() .'technology.dateupdated',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',
            'dateupdated',
        ];         
        $this->db->select($columns);

        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'technology.languageid', 'left');  

        if (isset($where['language']) && $where['language'] !== '') {
            $this->db->where(db_prefix() . 'languages.language', (string)$where['language']);
            return $this->db->get(db_prefix() . 'technology')->row();
        }

        if (!empty($where)) {
            $this->db->where($where);
        }    

        return  $this->db->get(db_prefix() . 'technology')->result();
        
    }  

    /**
     * Update company info
     * @param  array $data company data
     * @return boolean
     */    
    public function update($data)
	{  
        $data['description']         = nl2br($data['description'] ?? '');
        $data['long_description']    = html_purify($data['long_description'], true);

        $data['dateupdated']         = date('Y-m-d H:i:s');

        $this->db->where('languageid', $data['languageid']);
        $this->db->update(db_prefix() . 'technology', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Technology Updated', 'update');
            hooks()->do_action('after_update_technology', $data);

            return true;
        }

        return false;
    } 

    /**
     * Get Items
     *
     * @param string $id
     * @param array $where
     * @return void
     */
    public function get_items($id = '', $where = array())
    {

        $columns = [
            db_prefix() .'technology_items.id',
            db_prefix() .'technology_items.staffid',
            db_prefix() .'technology_items.file_name',
            db_prefix() .'technology_items.folder',
            db_prefix() .'technology_items.visible_draft',
            db_prefix() .'technology_items.dateadded',
            db_prefix() .'technology_items.order',
            db_prefix() .'technology_items_translation.name as name',
            db_prefix() .'technology_items_translation.description as description',            
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language', 
        ];           
        $this->db->select($columns);

        $this->db->where($where);
        $this->db->join(db_prefix() . 'technology_items_translation', db_prefix() . 'technology_items.id = ' . db_prefix() . 'technology_items_translation.itemid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'technology_items_translation.languageid', 'left');  

        $this->db->group_by(db_prefix() . 'technology_items_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'technology_items.id', $id);

            return $this->db->get(db_prefix() . 'technology_items')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'technology_items')->result();        
    }   
    

    public function add_items($data)
    {
        $languages = $this->languages_model->get(null, ['active' => 1]);

        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');
        $data['description']    = nl2br($data['description'] ?? '');

        $data = hooks()->apply_filters('before_add_items', $data);

        $this->db->insert(db_prefix() . 'technology_items', $data);
        $insert_id = $this->db->insert_id();  

        if ($insert_id) {
            if(isset($languages)){
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'technology_items_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'itemid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_items', $insert_id);
            log_activity('New Items Technology Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;        
    }

    public function update_items($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $affectedRows = 0;

        // Se houver tradução específica
        $languageid = $data['languageid'] ?? null;
        unset($data['languageid']);

        $data['description']    = nl2br($data['description'] ?? '');

        $updateMain = [
            'visible_draft' => $data['visible_draft'] ?? null,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'technology_items', $updateMain);  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }          
        
        if($languageid){
            $updateTranslation = [
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
            ];

            $this->db->where('itemid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'technology_items_translation', $updateTranslation);            
        }   

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }  

        if ($affectedRows > 0) {
            log_activity('Company Items Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_technology_items', $id);

            return true;
        }

        return false;
    }

    /**
     * Delete a technology item, its translations, and its picture (file + db reference).
     * Uses a DB transaction to keep consistency.
     *
     * @param int $id
     * @return bool
     */    
    public function delete_item($id)
    {
        
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
        
        hooks()->do_action('before_technology_items_deleted', $id);
        
        $this->db->trans_begin();
        // Use the same path that uploads used (technology/icons)
        $pictureOk = $this->delete_picture_items($id, rtrim(get_upload_path_by_type('technology'), '/') . '/icons/');
                
        $this->db->where('itemid', $id);
        $this->db->delete(db_prefix() . 'technology_items_translation');

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'technology_items');

        $rows = $this->db->affected_rows();

        // Commit or rollback
        if ($this->db->trans_status() === false || $rows <= 0 || !$pictureOk) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }  


    /**
     * Delete only the picture file for a technology item and null the DB field.
     *
     * @param int $id
     * @param string|null $customPath Optional override path (if you keep different stores)
     * @return bool
     */
    public function delete_picture_items($id, $customPath = null)
    {
        hooks()->do_action('before_remove_technology_items_pictures', $id);

        $this->db->where('id', (int)$id);
        $file = $this->db->get(db_prefix() . 'technology_items')->row();
        // If there is no row or no file, just ensure DB is clean and return true
        if (!$file) {
            return true;
        }

        // Respect "external" flag (if set)
        $isExternal = !empty($file->external);
        // Standardized path: use the same base used on upload (technology/icons)
        // If you truly need 'technology/icons', pass via $customPath when calling
        $basePath = $customPath ?: (rtrim(get_upload_path_by_type('technology'), '/') . '/icons/');
        $fileName = !empty($file->file_name) ? $file->file_name : null;

        // Remove physical file if local and we have a filename
        if (!$isExternal && $fileName) {
            $fullPath = $basePath . $fileName;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
            
        // Null the file reference in DB
        $this->db->where('id', (int)$id);
        $this->db->update(db_prefix() . 'technology_items', ['file_name' => null]);

        return true;
    }
    /** Get Pictures */

    public function get_pictures($id = '', $limit = false,  $where = array())
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'technology_pictures')->row();
        }
        if ($limit) {
            $this->db->limit(3);
        }
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'technology_pictures')->result();
    }  

    public function upload_picture($data)
	{  
		$this->db->insert(db_prefix() . 'technology_pictures', $data);  
        $insert_id = $this->db->insert_id(); 
        if ($insert_id) {
        
            return $insert_id;
        }
        
        return false;        
    }     

    public function delete_picture($id)
    {
        hooks()->do_action('before_remove_technology_picture', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'technology_pictures')->row();
        if ($file) {
            if (empty($file->external)) {
                $path     = get_upload_path_by_type('technology');
                $fullPath = $path . $file->file_name;     
                if (file_exists($fullPath)) {
                    @unlink($fullPath);                 
                }           
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'technology_pictures');  

        }  
        
        return true;
    }       

    /**
     * Get Videos
     *
     * @param array $where
     * @return void
     */
    public function get_videos($where = array())
    {
        $this->db->where($where);
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'technology_videos')->result();
    }   
    
    /**
     * Get Video id
     *
     * @param [type] $id
     * @return void
     */
    public function get_video($id)
    {
        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'technology_videos')->row();

        return $file;
    }  

    /**
     * Add Video
     *
     * @param [type] $data
     * @return void
     */
    public function add_video($data)
    {
        $data['description']            = nl2br($data['description'] ?? '');
        $data['visible_to_customer']    = $data['visible_to_customer'];
        $data['dateadded']              = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_video', $data);

        $this->db->insert(db_prefix() . 'technology_videos', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            hooks()->do_action('after_add_video', $insert_id);
            log_activity('New Video Company Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;        
    }   
    
    /**
     * Upda Video
     *
     * @param [type] $data
     * @return void
     */
    public function update_video($data, $id)
    {
        if (empty($data) || !$id) {
            return false;
        }

        $data['description']            = nl2br($data['description'] ?? '');
        $data['visible_to_customer']    = $data['visible_to_customer'];

        $data = hooks()->apply_filters('before_update_video', $data);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'technology_videos', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Update Video Company [ID: ' . $id . ']', 'update');
            hooks()->do_action('after_update_video', $id);

            return true;
        }   

        return false;        
    }       
    
    /**
     * Delete Video
     *
     * @param [type] $id
     * @param boolean $log_activity
     * @return void
     */
    public function delete_video($id)
    {
        hooks()->do_action('before_remove_technology_video', $id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'technology_videos');  

        if ($this->db->affected_rows() > 0) {
            return true;
        }
        
        return false;
    }  
    
	public function get_diagnosis()
	{
        $diagnosis = [
            [
                'id'                => 1,
                'name'              => "Plan Centinela — Diagnóstico bajo demanda.",
                'description'       => 'Diagnóstico sanitario inteligente, eficiente y no invasivo',
                'long_description'  => '                                
                <p>El Plan Centinela está diseñado para empresas que desean incorporar un esquema de diagnóstico planificado y flexible de forma eficiente. Permite capturar información sanitaria ambiental y activar diagnósticos poblacionales bajo demanda, sin necesidad de manipular animales ni alterar la rutina productiva. Es ideal para empresas que aún no cuentan con un presupuesto diagnóstico establecido o buscan hacer más eficiente su inversión sanitaria con una base científica sólida.</p>  
                <p><strong>Incluye:</strong></p>                                               
                <ul>
                    <li>Dispositivo CAPTUS® para captura de bioaerosoles en granjas, sin manipular animales.</li>
                    <li>Muestreo semanal con 54 kits incluidos por año.</li>
                    <li>Almacenamiento Activo & Diagnóstico On-Demand: las muestras se conservan hasta por tres meses, listas para ser procesadas cuando se requiera.</li>
                    <li>Evaluaciones sanitarias completas trimestrales sin costo adicional.</li>
                    <li>Plataforma Digital metaBIX Biotech®: integración de resultados, tendencias sanitarias y reportes técnicos accesibles en todo momento.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'es',
            ],
            [
                'id'                => 2,
                'name'              => "Plano Sentinela — Diagnóstico sob demanda.",
                'description'       => 'Diagnóstico sanitário inteligente, eficiente e não invasivo',
                'long_description'  => '
                <p>O Plano Sentinela foi pensado para empresas que desejam incorporar um esquema de diagnóstico planejado e flexível de forma eficiente. Ele permite capturar informações sanitárias ambientais e acionar diagnósticos populacionais sob demanda, sem manipular animais ou alterar a rotina produtiva. É ideal para empresas que ainda não possuem um orçamento diagnóstico definido ou buscam tornar seu investimento sanitário mais eficiente, com base científica sólida.</p>
                <p><strong>Inclui:</strong></p>
                <ul>
                    <li>Dispositivo CAPTUS® para captura de bioaerossóis nas granjas, sem manipular animais.</li>
                    <li>Coleta semanal com 54 kits incluídos por ano.</li>
                    <li>Armazenamento Ativo &amp; Diagnóstico On-Demand: as amostras podem ser preservadas por até três meses, prontas para processamento quando necessário.</li>
                    <li>Avaliações sanitárias completas trimestrais sem custo adicional.</li>
                    <li>Plataforma Digital metaBIX Biotech®: integração de resultados, tendências sanitárias e relatórios técnicos acessíveis a qualquer momento.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'pt',
            ],  
            [
                'id'                => 3,
                'name'              => "Sentinel Plan — On-demand diagnostics.",
                'description'       => 'Smart, efficient, and non-invasive health diagnostics.',
                'long_description'  => '
                <p>The Sentinel Plan is designed for companies seeking to implement a planned and flexible diagnostic scheme efficiently. It enables the capture of environmental health data and the activation of population-level diagnostics on demand, without handling animals or disrupting production routines. It is ideal for companies without an established diagnostic budget or those aiming to make their sanitary investment more efficient, backed by solid scientific evidence.</p>
                <p><strong>Includes:</strong></p>
                <ul>
                    <li>CAPTUS® device for bioaerosol capture on farms, with no need to handle animals.</li>
                    <li>Weekly sampling with 54 kits included per year.</li>
                    <li>Active Storage &amp; On-Demand Diagnostics: samples can be preserved for up to three months, ready for processing when required.</li>
                    <li>Comprehensive quarterly health evaluations at no additional cost.</li>
                    <li>metaBIX Biotech® Digital Platform: integration of results, health trends, and technical reports accessible anytime.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'en',
            ],    
            [
                'id'                => 4,
                'name'              => "Beneficios técnicos y estratégico",
                'description'       => '',
                'long_description'  => '                                
                <p>
                    Diagnóstico poblacional no invasivo: Captura información representativa del ambiente productivo sin intervención sobre los animales.
                    Eficiencia sanitaria y presupuestaria: Activá los análisis sólo cuando se requieran, evitando gastos innecesarios.
                    Visibilidad de la situación sanitaria: Accedé a indicadores de presión de infección ambiental de patógenos y tendencias por granja o unidad epidemiológica.
                    Optimización del retorno sanitario: Reducí tratamientos empíricos, mejora la eficiencia de tus decisiones y obtén un ROI sanitario medible.
                </p>
                <p>
                    Monitoreo continuo y predicción temprana de riesgos sanitarios.
                </p> 
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'es',
            ],
            [
                'id'                => 5,
                'name'              => "Benefícios técnicos e estratégicos",
                'description'       => '',
                'long_description'  => '
                <p>
                    Diagnóstico populacional não invasivo: captura informações representativas do ambiente produtivo sem interferir nos animais.<br>
                    Eficiência sanitária e orçamentária: ative as análises apenas quando necessário, evitando custos desnecessários.<br>
                    Visibilidade da situação sanitária: acesse indicadores de pressão de infecção ambiental de patógenos e tendências por granja ou unidade epidemiológica.<br>
                    Otimização do retorno sanitário: reduza tratamentos empíricos, aumente a eficiência das decisões e obtenha um ROI sanitário mensurável.<br>
                </p>
                <p>
                    Monitoramento contínuo e predição precoce de riscos sanitários.
                </p>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'pt',
            ],  
            [
                'id'                => 6,
                'name'              => "Technical and strategic benefits",
                'description'       => '',
                'long_description'  => '
                <p>
                    Non-invasive population diagnostics: captures representative information from the production environment without animal handling.<br>
                    Sanitary and budget efficiency: activate analyses only when required, avoiding unnecessary expenses.<br>
                    Sanitary status visibility: access indicators of environmental pathogen pressure and trends by farm or epidemiological unit.<br>
                    Optimization of sanitary ROI: reduce empirical treatments, improve decision-making efficiency, and achieve measurable sanitary ROI.<br>
                </p>
                <p>
                    Continuous monitoring and early prediction of sanitary risks.
                </p>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'en',
            ],   
            [
                'id'                => 7,
                'name'              => "Plan Vigilancia Sanitaria",
                'description'       => 'Monitoreo sistemático y visibilidad continua del riesgo sanitario.',
                'long_description'  => '
                <p>El Plan Vigilancia Sanitaria está orientado a empresas con sistemas intensivos o integraciones que requieren monitoreo sistemático y visibilidad continua del riesgo sanitario. Combina la captura ambiental semanal con análisis qPCR y algoritmos predictivos que permiten detectar variaciones en la presión de infección ambiental (PIA) y anticipar posibles brotes. Es la opción ideal para empresas con estructura diagnóstica consolidada que buscan fortalecer la prevención y la gestión basada en datos.</p>  
                <p><strong>Incluye:</strong></p>                                               
                <ul>
                    <li>Dispositivo CAPTUS® con kits de muestreo ambiental semanal (54 por año).</li>
                    <li>Procesamiento qPCR continuo de tres patógenos fijos.</li>
                    <li>Líneas base sanitarias personalizadas por granja y zona.</li>
                    <li>Plataforma Digital metaBIX Biotech® con dashboards, alertas y comparativos.</li>
                    <li>Reportes trimestrales con análisis de tendencias y recomendaciones.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'es',
            ],

            [
                'id'                => 8,
                'name'              => "Plano de Vigilância Sanitária",
                'description'       => 'Monitoramento sistemático e visibilidade contínua do risco sanitário.',
                'long_description'  => '
                <p>O Plano de Vigilância Sanitária é voltado para empresas com sistemas intensivos ou integrações que exigem monitoramento sistemático e visibilidade contínua do risco sanitário. Ele combina a captura ambiental semanal com análises de qPCR e algoritmos preditivos que permitem detectar variações na pressão de infecção ambiental (PIA) e antecipar possíveis surtos. É a opção ideal para empresas com estrutura diagnóstica consolidada que buscam fortalecer a prevenção e a gestão baseada em dados.</p>  
                <p><strong>Inclui:</strong></p>                                               
                <ul>
                    <li>Dispositivo CAPTUS® com kits de amostragem ambiental semanal (54 por ano).</li>
                    <li>Processamento qPCR contínuo de três patógenos fixos.</li>
                    <li>Linha de base sanitária personalizada por granja e região.</li>
                    <li>Plataforma Digital metaBIX Biotech® com dashboards, alertas e comparativos.</li>
                    <li>Relatórios trimestrais com análise de tendências e recomendações.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'pt',
            ],

            [
                'id'                => 9,
                'name'              => "Sanitary Surveillance Plan",
                'description'       => 'Systematic monitoring and continuous visibility of sanitary risk.',
                'long_description'  => '
                <p>The Sanitary Surveillance Plan is designed for companies with intensive systems or integrations that require systematic monitoring and continuous visibility of sanitary risk. It combines weekly environmental sampling with qPCR analysis and predictive algorithms that detect variations in Environmental Infection Pressure (EIP) and anticipate potential outbreaks. It is the ideal option for companies with a consolidated diagnostic structure looking to strengthen prevention and data-driven management.</p>  
                <p><strong>Includes:</strong></p>                                               
                <ul>
                    <li>CAPTUS® device with weekly environmental sampling kits (54 per year).</li>
                    <li>Continuous qPCR processing of three fixed pathogens.</li>
                    <li>Customized sanitary baseline per farm and region.</li>
                    <li>metaBIX Biotech® Digital Platform with dashboards, alerts, and comparisons.</li>
                    <li>Quarterly reports with trend analysis and recommendations.</li>
                </ul>
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'en',
            ],     
            [
                'id'                => 10,
                'name'              => "Beneficios técnicos y estratégicos",
                'description'       => 'Predicción temprana de brotes y monitoreo ambiental inteligente.',
                'long_description'  => '
                <p>
                    Predicción temprana de brotes: detecta desviaciones antes de la manifestación clínica.<br>
                    Monitoreo ambiental inteligente: visualiza la Presión de Infección Ambiental por patógeno.<br>
                    Benchmarking sanitario: compara tu desempeño frente a otras granjas.<br>
                    Gestión integrada: reportes centralizados y asistencia técnica.<br>
                    Retorno comprobado: ROI ≥ 5x anual.
                </p>  
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'es',
            ],

            [
                'id'                => 11,
                'name'              => "Benefícios técnicos e estratégicos",
                'description'       => 'Predição precoce de surtos e monitoramento ambiental inteligente.',
                'long_description'  => '
                <p>
                    Predição precoce de surtos: detecta desvios antes da manifestação clínica.<br>
                    Monitoramento ambiental inteligente: visualize a Pressão de Infecção Ambiental por patógeno.<br>
                    Benchmarking sanitário: compare seu desempenho com outras granjas.<br>
                    Gestão integrada: relatórios centralizados e suporte técnico.<br>
                    Retorno comprovado: ROI ≥ 5x ao ano.
                </p>  
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'pt',
            ],

            [
                'id'                => 12,
                'name'              => "Technical and Strategic Benefits",
                'description'       => 'Early outbreak prediction and smart environmental monitoring.',
                'long_description'  => '
                <p>
                    Early outbreak prediction: detects deviations before clinical manifestation.<br>
                    Smart environmental monitoring: visualize Environmental Infection Pressure by pathogen.<br>
                    Sanitary benchmarking: compare your performance against other farms.<br>
                    Integrated management: centralized reports and technical support.<br>
                    Proven return: ROI ≥ 5x annually.
                </p>  
                ',
                'order'             => 0,
                'visible_draft'     => 0,
                'language'          => 'en',
            ],                                                 
        ];

        return $diagnosis;        
    }    

	public function get_precision()
	{
        $precision = [
            [
                'id'                => 1,
                'name'              => "Precisión Avanzada.",
                'description'       => 'Tecnología de detección temprana que identifica amenazas con exactitud, permitiendo acciones dirigidas y eficientes.',
                'order'             => 0,
                'language'          => 'es',
            ],
            [
                'id'          => 2,
                'name'        => "Precisão Avançada.",
                'description' => 'Tecnologia de detecção precoce que identifica ameaças com precisão, permitindo ações direcionadas e eficientes.',
                'order'       => 0,
                'language'    => 'pt',
            ],  
            [
                'id'          => 3,
                'name'        => "Advanced Precision.",
                'description' => 'Early detection technology that accurately identifies threats, enabling targeted and efficient actions.',
                'order'       => 0,
                'language'    => 'en',
            ], 
            [
                'id'                => 4,
                'name'              => "Escalabilidad Completa",
                'description'       => 'Solución adaptable desde la producción animal hasta cultivos agrícolas e industria de alimentos.',
                'order'             => 0,
                'language'          => 'es',
            ],
            [
                'id'                => 5,
                'name'              => "Escalabilidade Completa",
                'description'       => 'Solução adaptável desde a produção animal até cultivos agrícolas e indústria de alimentos.',
                'order'             => 0,
                'language'          => 'pt',
            ],
            [
                'id'                => 6,
                'name'              => "Full Scalability",
                'description'       => 'Adaptable solution from animal production to agricultural crops and the food industry.',
                'order'             => 0,
                'language'          => 'en',
            ],      
            [
                'id'                => 7,
                'name'              => "Impacto Transversal",
                'description'       => 'Protege negocios, reduce costos con tratamientos y pérdidas productivas, y previene crisis sanitarias que afectan a toda la sociedad.',
                'order'             => 0,
                'language'          => 'es',
            ],
            [
                'id'                => 8,
                'name'              => "Impacto Transversal",
                'description'       => 'Protege negócios, reduz custos com tratamentos e perdas produtivas e previne crises sanitárias que afetam toda a sociedade.',
                'order'             => 0,
                'language'          => 'pt',
            ],
            [
                'id'                => 9,
                'name'              => "Cross-Sector Impact",
                'description'       => 'Protects businesses, reduces treatment and productivity loss costs, and prevents health crises that affect society as a whole.',
                'order'             => 0,
                'language'          => 'en',
            ],                                       
        ];

        return $precision; 
    }    
}