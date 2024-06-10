<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">LISTAGEM  DE CONTA</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/listar-contas">Dashboard</a></li>
              <li class="breadcrumb-item active">Listagem</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                <a href="/contas/create" class="btn btn-info btn-sm mx-1"> <i class="fas fa-plus"></i>CRIAR CONTAS</a>

                <button class="btn float-right btn-danger btn-sm" @click="imprimirContas()">
                  <i class="fas fa-save"></i> Visualizar
                </button>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-hover" id="tabela_de_contas">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Código</th>
                      <th>Conta</th>
                      <th>Classe</th>
                      <th>Estado</th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr v-for="item in contas" :key="item">
                      <td>#</td>
                      <td>{{ item.conta.numero }}</td>
                      <td>{{ item.conta.designacao }}</td>
                      <td>{{ item.classe.numero }} - {{ item.classe.designacao }}</td>
                      <td class="text-capitalize">{{ item.estado }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>

<script>

export default {

  props: [
    'contas'
  ],
  components: {
  },
  computed: {
  },
  mounted(){
    $("#tabela_de_contas").DataTable({
      responsive: true,
      lengthChange: true,
      autoWidth: true,
    });
  },
  data() {

  },

  methods: {
  
    // updateData() {
    //   this.$Progress.start();
    //   this.$inertia.get("/contas", this.params, {
    //     preserveState: true,
    //     preverseScroll: true,
    //     onSuccess: () => {
    //       this.$Progress.finish();
    //     },
    //   });
    // },
  
    imprimirContas() {
      // window.open("/estudante/lista-avaliacao/" + btoa(btoa(btoa(this.usuario))) + '/' +  btoa(btoa(btoa(this.ano_lectivo))) + '/' + btoa(btoa(btoa(this.query.id_semestre))));
      window.open("imprimir-contas");
    },
    mudar_estado(item) {
      this.$Progress.start();

      // axios.get(`/contas/${item.id}`)
      //   .then((response) => {
      //     this.$Progress.finish();
      //     Swal.fire({
      //       toast: true,
      //       icon: "success",
      //       title: "Estado Alterado com sucesso!",
      //       animation: false,
      //       position: "top-end",
      //       showConfirmButton: false,
      //       timer: 4000
      //     })
      
      //     window.location.reload();
      //   })
      //   .catch((error) => {
          
      //     this.$Progress.fail();
      //     Swal.fire({
      //       toast: true,
      //       icon: "danger",
      //       title: "Correu um erro ao Estado Alterado com sucesso!",
      //       animation: false,
      //       position: "top-end",
      //       showConfirmButton: false,
      //       timer: 4000
      //     })
          
      //   });
    },
  },
};
</script>