<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">EDITAR SUBCONTAS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/listar-sub-contas">Listagem</a></li>
              <li class="breadcrumb-item active">Editar subcontas</li>
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
                      <label for="conta_id" class="form-label">Contas</label>
                      <select id="conta_id" class="col-12 col-md-12 form-control" v-model="form.conta_id">
                        <option value="">Selecionar Contas</option>
                        <option :value="item.conta.id" v-for="item in contas" :key="item.id">{{ item.conta.numero }} - {{ item.conta.designacao }}</option>
                      </select>
                      
                      <span
                        class="text-danger"
                        v-if="form.errors && form.errors.conta_id"
                        >{{ form.errors.conta_id }}</span
                      >
                    </div>
                    
                    <div class="col-12 col-md-3 mb-4">
                      <label for="tipo" class="form-label">Tipos</label>
                      <select id="tipo" class="col-12 col-md-12 form-control" v-model="form.tipo">
                        <option value="">Selecionar Tipos</option>
                        <option :value="item.id" v-for="item in tipos" :key="item.id">{{ item.text }}</option>
                      </select>
                      
                      <span class="text-danger" v-if="form.errors && form.errors.tipo">{{ form.errors.tipo }}</span>
                    </div>

                    <div class="col-12 col-md-3 mb-4">
                      <label for="numero" class="form-label"
                        >Código da SubConta</label
                      >
                      <input
                        type="text"
                        id="numero"
                        v-model="form.numero"
                        class="form-control"
                        placeholder="Ex: 1.1, 1.1.1"
                      />
                      <span
                        class="text-danger"
                        v-if="form.errors && form.errors.numero"
                        >{{ form.errors.numero }}</span
                      >
                    </div>
                    
                    <div class="col-12 col-md-6 mb-4">
                      <label for="designacao" class="form-label"
                        >Designação</label
                      >
                      <input
                        type="text"
                        id="designacao"
                        v-model="form.designacao"
                        class="form-control"
                        placeholder="Informe o designação da subconta:"
                      />
                      <span
                        class="text-danger"
                        v-if="form.errors && form.errors.designacao"
                        >{{ form.errors.designacao }}</span
                      >
                    </div>

                    <div class="col-12 col-md-6 mb-4">
                      <label for="estado" class="form-label">Estado</label>
                      <select id="estado" class="col-12 col-md-12 form-control" v-model="form.estado">
                        <option value="">Selecionar Estado</option>
                        <option :value="item.id" v-for="item in estados" :key="item.id">{{ item.text }}</option>
                      </select>
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
  props: ["contas", "subconta"],
  data() {
    return {
      estados: [
        {'id': "activo", 'text': "Activo"},
        {'id': "desactivo", 'text': "Desactivo"},
      ],
      
      tipos: [
        {'id': "R", 'text': "Geral"},
        {'id': "M", 'text': "Movimento"},
        {'id': "I", 'text': "Intregadora"},
      ],
      
      form: this.$inertia.form({
        conta_id: this.subconta.conta_id ?? "",
        designacao: this.subconta.designacao ?? "",
        numero: this.subconta.numero ?? "",
        estado: this.subconta.estado ?? "",
        tipo: this.subconta.tipo ?? "",
        itemId: this.subconta.id ?? "",
      }),
    };
  },
  mounted() {
    // console.log()
  },
  methods: {
    submit() {
      this.$Progress.start();
      
      this.form.put("/listar-sub-contas/" + this.form.itemId, {
        preverseScroll: true,
        onSuccess: () => {
          this.isUpdate = false;
          this.itemId = null;
          this.form.reset();

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
        },
        onError: (errors) => {
          sweetError("Ocorreu um erro ao actualizar Perfil!");
        },
      });
      
      // axios
      //   .put(`/listar-sub-contas/${this.form.itemId}`, this.form)
      //   .then((response) => {
      //     // this.form.reset();
      //     this.$Progress.finish();

      //     Swal.fire({
      //       toast: true,
      //       icon: "success",
      //       title: "Dados Salvos com Sucesso!",
      //       animation: false,
      //       position: "top-end",
      //       showConfirmButton: false,
      //       timer: 4000,
      //     });

      //     window.location.reload();
      //     console.log("Resposta da requisição POST:", response.data);
      //   })
      //   .catch((error) => {
      //     // sweetError("Ocorreu um erro ao actualizar Instituição!");
      //     this.$Progress.fail();
      //     Swal.fire({
      //       toast: true,
      //       icon: "danger",
      //       title: "Correu um erro ao salvar os dados!",
      //       animation: false,
      //       position: "top-end",
      //       showConfirmButton: false,
      //       timer: 4000,
      //     });

      //     console.error("Erro ao fazer requisição POST:", error);
      //   });
    },
  },
};
</script>
    