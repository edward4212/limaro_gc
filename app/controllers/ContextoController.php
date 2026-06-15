<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{FodaModel, ParteInteresadaModel};

class ContextoController extends Controller
{
    // ══════════════════════════════════════════════════════════
    //  §4.1 ANÁLISIS FODA
    // ══════════════════════════════════════════════════════════

    public function foda(): void
    {
        $model = new FodaModel();
        $this->view('contexto/foda/index', [
            'pageTitle' => 'Análisis DOFA',
            'items'     => $model->listar(
                Request::get('tipo', ''),
                Request::get('impacto', '')
            ),
            'resumen'   => $model->resumen(),
            'filtro_tipo'    => Request::get('tipo', ''),
            'filtro_impacto' => Request::get('impacto', ''),
        ]);
    }

    public function fodaCrear(): void
    {
        $this->view('contexto/foda/form', [
            'pageTitle' => 'Nuevo Elemento DOFA',
            'item' => null,
        ]);
    }

    public function fodaGuardar(): void
    {
        Csrf::verify();
        $data = Request::only(['tipo','descripcion','impacto','estrategia']);
        if (empty(trim($data['descripcion'] ?? ''))) {
            Session::flash('error', 'La descripción es obligatoria.');
            $this->redirect('/contexto/foda/crear');
            return;
        }
        $data['id_usuario'] = Auth::id();
        $model = new FodaModel();
        $id = $model->insert($data);
        registrarAuditoria('contexto','CREAR','foda',$id,null,$data);
        $this->redirectSuccess('/contexto/foda', 'Elemento DOFA registrado.');
    }

    public function fodaEditar(int $id): void
    {
        $model = new FodaModel();
        $item  = $model->find($id);
        if (!$item) $this->abort(404);
        $this->view('contexto/foda/form', [
            'pageTitle' => 'Editar Elemento DOFA',
            'item' => $item,
        ]);
    }

    public function fodaActualizar(int $id): void
    {
        Csrf::verify();
        $data  = Request::only(['tipo','descripcion','impacto','estrategia']);
        $model = new FodaModel();
        $antes = $model->find($id);
        if (!$antes) $this->abort(404);
        $model->update($id, $data);
        registrarAuditoria('contexto','EDITAR','foda',$id,$antes,$data);
        $this->redirectSuccess('/contexto/foda', 'Elemento actualizado.');
    }

    public function fodaEliminar(int $id): void
    {
        Csrf::verify();
        if (!Auth::puede('contexto_foda','eliminar')) $this->abort(403);
        $model = new FodaModel();
        $model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/contexto/foda', 'Elemento eliminado.');
    }

    // ══════════════════════════════════════════════════════════
    //  §4.2 PARTES INTERESADAS
    // ══════════════════════════════════════════════════════════

    public function partes(): void
    {
        $model = new ParteInteresadaModel();
        $this->view('contexto/partes/index', [
            'pageTitle'  => 'Partes Interesadas',
            'items'      => $model->listar(Request::get('tipo', '')),
            'resumen'    => $model->resumen(),
            'filtro_tipo'=> Request::get('tipo', ''),
        ]);
    }

    public function partesCrear(): void
    {
        $this->view('contexto/partes/form', [
            'pageTitle' => 'Nueva Parte Interesada',
            'item' => null,
        ]);
    }

    public function partesGuardar(): void
    {
        Csrf::verify();
        $data = Request::only(['nombre','tipo','necesidades','expectativas',
                               'nivel_influencia','nivel_interes']);
        if (empty(trim($data['nombre'] ?? ''))) {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/contexto/partes-interesadas/crear');
            return;
        }
        $data['id_usuario'] = Auth::id();
        $model = new ParteInteresadaModel();
        $id = $model->insert($data);
        registrarAuditoria('contexto','CREAR','parte_interesada',$id,null,$data);
        $this->redirectSuccess('/contexto/partes-interesadas', 'Parte interesada registrada.');
    }

    public function partesEditar(int $id): void
    {
        $model = new ParteInteresadaModel();
        $item  = $model->find($id);
        if (!$item) $this->abort(404);
        $this->view('contexto/partes/form', [
            'pageTitle' => 'Editar Parte Interesada',
            'item' => $item,
        ]);
    }

    public function partesActualizar(int $id): void
    {
        Csrf::verify();
        $data  = Request::only(['nombre','tipo','necesidades','expectativas',
                                'nivel_influencia','nivel_interes']);
        $model = new ParteInteresadaModel();
        $antes = $model->find($id);
        if (!$antes) $this->abort(404);
        $model->update($id, $data);
        registrarAuditoria('contexto','EDITAR','parte_interesada',$id,$antes,$data);
        $this->redirectSuccess('/contexto/partes-interesadas', 'Parte interesada actualizada.');
    }

    public function partesEliminar(int $id): void
    {
        Csrf::verify();
        if (!Auth::puede('contexto_partes','eliminar')) $this->abort(403);
        $model = new ParteInteresadaModel();
        $model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/contexto/partes-interesadas', 'Parte interesada eliminada.');
    }
}
