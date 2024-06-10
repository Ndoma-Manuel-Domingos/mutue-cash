<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">EDITAR TIPO DE DOCUMENTO</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/tipos-documentos">Listagem</a></li>
              <li class="breadcrumb-item active">Editar Tipo de Documento</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <form @submit.prevent="submit">
              <div class="card">
                <div class="card-header"></div>

                <div class="card-body">
                  <div class="row">
                    <div class="col-12 col-md-6 mb-4">
                      <label for="diario_id" class="form-label">Diários</label>
                      <Select2 v-model="form.diario_id"
                        id="diario_id" class="col-12 col-md-12"
                        :options="diarios" :settings="{ width: '100%' }" 
                      />
                      <span class="text-danger" v-if="form.errors && form.errors.diario_id">{{ form.errors.diario_id }}</span>
                    </div>
                    
                    <div class="col-12 col-md-6 mb-4">
                      <label for="numero" class="form-label">Número</label>
                      <input type="text" id="numero" v-model="form.numero" class="form-control" placeholder="Número Ex: 1.1">
                      <span class="text-danger" v-if="form.errors && form.errors.numero">{{ form.errors.numero }}</span>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                      <label for="designacao" class="form-label">Designação</label>
                      <input type="text" id="designacao" v-model="form.designacao" class="form-control" placeholder="Designação">
                      <span class="text-danger" v-if="form.errors && form.errors.designacao">{{ form.errors.designacao }}</span>
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                      <label for="estado" class="form-label">Estado</label>
                      <Select2 v-model="form.estado"
                        id="estado" class="col-12 col-md-12"
                        :options="estados" :settings="{ width: '100%' }" 
                      />
                      <span class="text-danger" v-if="form.errors && form.errors.estado">{{ form.errors.estado }}</span>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <button class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
    
<script>
export default {
  props: ["tipo_documento", "diarios"],
  computed: {
    user() {
      return this.$page.props.auth.user;
    },
        sessions() {
      return this.$page.props.sessions.empresa_sessao;
    },
    sessions_exercicio() {
      return this.$page.props.sessions.exercicio_sessao;
    },
  },
  data() {
    return {
      estados: [
        {'id': "activo", 'text': "Activo"},
        {'id': "desactivo", 'text': "Desactivo"},
      ],
      
      form: {
        designacao: this.tipo_documento.designacao ?? "",
        diario_id: this.tipo_documento.diario_id ?? "",
        numero: this.tipo_documento.numero ?? "",
        estado: this.tipo_documento.estado ?? "",
        itemId: this.tipo_documento.id ?? "",
      },
    };
  },
  mounted() {},
  methods: {
  
    getSubContas({ id, text }) {
      axios
        .get(`/get-conta/${this.form.conta_id}`)
        .then((response) => {
          console.log(response.data);
          this.form.numero = response.data.conta.numero + ".";
          this.subcontas = [];
          this.subcontas = response.data.subcontas;
        })
        .catch((error) => {});
    },


    submit() {
      this.$Progress.start();
      axios
        .put(`/tipos-documentos/${this.form.itemId}`, this.form)
        .then((response) => {
          // this.form.reset();
          this.$Progress.finish();

          Swal.fire({
            toast: true,
            icon: "success",
            title: "Dados Salvos com Sucesso!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000,
          });

          window.location.reload();
          console.log("Resposta da requisição POST:", response.data);
        })
        .catch((error) => {
          // sweetError("Ocorreu um erro ao actualizar Instituição!");
          this.$Progress.fail();
          Swal.fire({
            toast: true,
            icon: "danger",
            title: "Correu um erro ao salvar os dados!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000,
          });

          console.error("Erro ao fazer requisição POST:", error);
        });
    },
  },
};
</script>
    