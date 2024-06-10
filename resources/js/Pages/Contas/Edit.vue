<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">EDITAR CONTAS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/contas">Listagem</a></li>
              <li class="breadcrumb-item active">Editar contas</li>
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
                      <label for="" class="form-label">Classes</label>
                      <Select2 v-model="form.classe_id"
                        id="classe_id" class="col-12 col-md-12"
                        :options="classes" :settings="{ width: '100%' }" 
                      />
                      <span
                        class="text-danger"
                        v-if="form.errors && form.errors.classe_id"
                        >{{ form.errors.classe_id }}</span
                      >
                    </div>
                    
                    
                    <div class="col-12 col-md-6 mb-4">
                      <label for="nome" class="form-label">Contas</label>
                      <Select2 v-model="form.conta_id"
                        id="conta_id" class="col-12 col-md-12"
                        :options="contas" :settings="{ width: '100%' }" 
                        @select="getSubContas($event)"
                      />
                      <span class="text-danger" v-if="form.errors && form.errors.conta_id">{{ form.errors.conta_id }}</span>
                    </div>
                                      
                    <div class="col-12 col-md-6 mb-4">
                      <label for="numero" class="form-label">Código Conta</label>
                      <input type="text" id="numero" v-model="form.numero" class="form-control" placeholder="Ex: 1.1, 1.1.1">
                      <span class="text-danger" v-if="form.errors && form.errors.numero">{{ form.errors.numero }}</span>
                    </div>
                  

                    <div class="col-12 col-md-6 mb-4">
                      <label for="" class="form-label">Estados</label>
                      <Select2 v-model="form.estado"
                        id="estado" class="col-12 col-md-12"
                        :options="estados" :settings="{ width: '100%' }" 
                      />
                      <span
                        class="text-danger"
                        v-if="form.errors && form.errors.estado"
                        >{{ form.errors.estado }}</span
                      >
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
  props: ["conta", "classes", "contas"],
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
      
      tipos: [
        {'id': "M", 'text': "Movimento"},
        {'id': "E", 'text': "Entregadora"},
      ],
      
      form: {
        classe_id: this.conta.classe_id ?? "",
        conta_id: this.conta.conta_id ?? "",
        numero: this.conta.numero ?? "",
        tipo: this.conta.tipo ?? "",
        estado: this.conta.estado ?? "",
        itemId: this.conta.id ?? "",
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
        .put(`/contas/${this.form.itemId}`, this.form)
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
    