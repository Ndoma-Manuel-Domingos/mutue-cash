<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">CRIAR SUBCONTAS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <a href="/listar-sub-contas">Listagem</a>
              </li>
              <li class="breadcrumb-item active">Criar subContas</li>
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
        
        <!-- <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
         
              <div class="card-body">
                  <table class="table table-bordered table-hover" id="tabela_de_contas_relacionadas">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Conta</th>
                        <th>Tipo</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr v-for="item in subcontas" :key="item">
                        <td>#</td>
                        <td>{{ item.numero }}</td>
                        <td>{{ item.designacao }}</td>
                        <td>{{ item.tipo }}</td>
                      </tr>
                    </tbody>
                  </table>
              </div>
            </div>
          </div>
        </div> -->
      </div>
    </div>
  </MainLayouts>
</template>
    
<script>
export default {
  props: ["contas", "subcontas"],
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
        { id: "activo", text: "Activo" },
        { id: "desactivo", text: "Desactivo" },
      ],
      
      subcontas: [
      ],
      
      tipos: [
        {'id': "R", 'text': "Geral"},
        {'id': "M", 'text': "Movimento"},
        {'id': "I", 'text': "Intregadora"},
      ],

      form: this.$inertia.form({
        conta_id: "",
        designacao: "",
        numero: "",
        tipo: "",
        estado: "activo",
      }),
    };
  },
  mounted() {
    $('#tabela_de_contas_relacionadas').DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
    });
  },
  methods: {
    submit() {
      this.$Progress.start();
      
      this.form.post(route("listar-sub-contas.store"), {
        preverseScroll: true,
        onSuccess: () => {
          this.form.reset();
          this.$Progress.finish();

          Swal.fire({
            title: "Bom Trabalho",
            text: "Subconta Criada com Sucesso",
            icon: "success",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          // this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
        },
        onError: (errors) => {
          // this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
          console.log(errors);
          this.$Progress.fail();
        },
      });
  
      // axios
      //   .post(route("listar-sub-contas.store"), this.form)
      //   .then((response) => {
      //     // this.form.reset();
      //     this.$Progress.finish();

      //     // this.getSubContas();
          
      //     Swal.fire({
      //       toast: true,
      //       icon: "success",
      //       title: "Dados Salvos com Sucesso!",
      //       animation: false,
      //       position: "top-end",
      //       showConfirmButton: false,
      //       timer: 4000,
      //     });
          
      //     // window.location.reload();
      //     // console.log("Resposta da requisição POST:", response.data);
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
    